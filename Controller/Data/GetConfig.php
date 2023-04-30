<?php

/**
 * Instant_Checkout
 *
 * @package   Instant_Checkout
 * @author    Instant <hello@instant.one>
 * @copyright 2022 Copyright Instant. https://www.instantcheckout.com.au/
 * @license   https://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      https://www.instantcheckout.com.au/
 */

namespace Instant\Checkout\Controller\Data;

use Exception;
use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Service\DoRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class GetConfig extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CompositeConfigProvider
     */
    protected $configProvider;
    /**
     * @var InstantHelper
     */
    private $instantHelper;
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var GuestCartManagementInterface
     */
    protected $cartManagement;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StoreManagerInterface $storeManager,
        CompositeConfigProvider $configProvider,
        InstantHelper $instantHelper,
        DoRequest $doRequest,
        ResultJsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        CartRepositoryInterface $quoteRepository,
        GuestCartManagementInterface $cartManagement,
        QuoteFactory $quoteFactory,
        CurrencyFactory $currencyFactory,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->instantHelper = $instantHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->quoteFactory = $quoteFactory;
        $this->doRequest = $doRequest;
        $this->currencyFactory = $currencyFactory;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;

        return parent::__construct($context);
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $data['enableMinicartBtn'] = $this->instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $this->instantHelper->getInstantAppId();
        $data['cartId'] = $this->instantHelper->getSessionCartId();
        $data['enableSandbox'] = $this->instantHelper->getSandboxEnabledConfig();
        $data['disabledForSkusContaining'] = $this->instantHelper->getDisabledForSkusContaining();
        $data['storeCode'] = $this->storeManager->getStore()->getCode();
        $data['mcBtnWidth'] = $this->instantHelper->getMcBtnWidth();
        $data['cpageBtnWidth'] = $this->instantHelper->getCPageBtnWidth();
        $data['shouldResizeCartIndexBtn'] = $this->instantHelper->getShouldResizeCartIndexBtn();
        $data['shouldResizePdpBtn'] = $this->instantHelper->getShouldResizePdpBtn();
        $data['disabledForCustomerGroup'] = $this->instantHelper->getDisabledForCustomerGroup();
        $data['currentCurrencyCode'] = $this->instantHelper->getCurrentCurrencyCode();
        $data['baseCurrencyCode'] = $this->instantHelper->getBaseCurrencyCode();

        $data['swipeToBuy'] = [
            'enabled' => $this->instantHelper->getStbEnabled(),
            'height' => $this->instantHelper->getStbHeight(),
            'borderRadius' => $this->instantHelper->getStbBorderRadius(),
            'bottomLayerBorderColour' =>  $this->instantHelper->getStbBottomLayerBorderColour(),
            'bottomLayerBackgroundColour' => $this->instantHelper->getStbBottomLayerBackgroundColour(),
            'topLayerBackgroundColour' => $this->instantHelper->getStbTopLayerBackgroundColour(),
            'topLayerTextColour' => $this->instantHelper->getStbTopLayerTextColour(),
            'bottomLayerTextColour' => $this->instantHelper->getStbBottomLayerTextColour(),
            'thumbBackgroundColour' => $this->instantHelper->getStbThumbBackgroundColour(),
            'fontFamily' => $this->instantHelper->getStbFontFamily(),
            'topLayerFontSize' => $this->instantHelper->getStbTopLayerFontSize(),
            'bottomLayerFontSize' =>  $this->instantHelper->getStbBottomLayerFontSize(),
            'fontWeight' => $this->instantHelper->getStbFontWeight(),
        ];

        $data['mcBtnCustomStyle'] = $this->instantHelper->getMcBtnCustomStyle();
        $data['mcBtnContainerCustomStyle'] = $this->instantHelper->getMcBtnContainerCustomStyle();
        $data['mcBtnHideOrStrike'] = $this->instantHelper->getMcBtnShouldHideOrStrike();

        $data['cindexBtnCustomStyle'] = $this->instantHelper->getCindexBtnCustomStyle();
        $data['cindexBtnContainerCustomStyle'] = $this->instantHelper->getCindexBtnContainerCustomStyle();
        $data['cindexBtnHideOrStrike'] = $this->instantHelper->getCindexBtnShouldHideOrStrike();

        $data['cpageBtnCustomStyle'] = $this->instantHelper->getCpageBtnCustomStyle();
        $data['cpageBtnContainerCustomStyle'] = $this->instantHelper->getCpageBtnContainerCustomStyle();
        $data['cpageBtnHideOrStrike'] = $this->instantHelper->getCpageBtnShouldHideOrStrike();

        $sessionId = session_id();
        if (!empty($sessionId)) {
            $data['sessionId'] = session_id();
        }

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerRepository->getById($this->customerSession->getId());
            $data['customer'] = [
                'email' => $customer->getEmail(),
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
            ];

            $shippingAddressId = $customer->getDefaultShipping();
            if ($shippingAddressId) {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);
                $defaultShippingAddress = [
                    "address1" => $shippingAddress->getStreet()[0],
                    "address2" => count($shippingAddress->getStreet()) == 2 ? $shippingAddress->getStreet()[1] : '',
                    "city" => $shippingAddress->getCity(),
                    "regionCode" => $shippingAddress->getRegion()->getRegionCode(),
                    "postCode" => $shippingAddress->getPostcode(),
                    "countryCode" => $shippingAddress->getCountryId()
                ];
                $data['address'] = $defaultShippingAddress;
                $data['customer']['phone'] = $shippingAddress->getTelephone();
            }
        }

        $result->setData($data);


        $customer = $this->customerRepository->getById(85607);

        if ($customer->getCustomAttribute('mobile_number')) {
            $attributeValue = $customer->getCustomAttribute('mobile_number')->getValue();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('CUSTOMER MOBILE PHONE');
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug($attributeValue);
        } else {
            throw new Exception("NO MOBILE NUMBER");
        }

        return $result;
    }
}
