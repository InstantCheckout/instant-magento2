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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class GetConfig extends Action implements HttpGetActionInterface
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
     * @var DoRequest
     */
    private $doRequest;
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var MaskedQuoteIdToQuoteId
     */
    protected $quoteIdMaskFactory;
    /**
     * @var GuestCartManagementInterface
     */
    protected $cartManagement;
    /**
     * @var MaskedQuoteIdToQuoteId
     */
    protected $maskedQuoteIdToQuoteId;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
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
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CartRepositoryInterface $quoteRepository,
        GuestCartManagementInterface $cartManagement,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CurrencyFactory $currencyFactory,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->instantHelper = $instantHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->doRequest = $doRequest;
        $this->currencyFactory = $currencyFactory;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;

        return parent::__construct($context);
    }


    /**
     * Get equivalent invisible quote from current checkout quote.
     *
     * @return string
     */
    public function getInvisibleCartFromCart()
    {
        try {
            // Get checkout session quote
            $quote = $this->checkoutSession->getQuote();
            /** @var Quote $currentCart */
            $currentCart = $this->quoteRepository->get($quote->getEntityId());

            // Create an empty cart
            $maskedId = $this->cartManagement->createEmptyCart();

            // Get quote ID of new empty cart
            $newCartId = $this->maskedQuoteIdToQuoteId->execute($maskedId);

            // Load new cart
            /** @var Quote $newCart */
            $newCart = $this->quoteFactory->create()->load($newCartId, 'entity_id');
            $newCart->setActive(1);
            $newCart->setCouponCode($currentCart->getCouponCode());

            // For each item in the current cart; copy over to new cart
            foreach ($currentCart->getAllVisibleItems() as $item) {
                $newItem = clone $item;
                $newCart->addItem($newItem);
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setParentItem($newItem);
                        $newCart->addItem($newChild);
                    }
                }
            }

            // Init shipping & billing
            if (!$newCart->getId()) {
                $newCart->getShippingAddress();
                $newCart->getBillingAddress();
            }

            // Save
            $newCart->setId($newCartId);
            $newCart->collectTotals()->save();
            $newCart->save();

            return $maskedId;
        } catch (Exception $e) {
            $this->logger->error("Exception raised in Instant/Checkout/Controller/Data/GetConfig");
            $this->logger->error($e->getMessage());
            return '';
        }
    }

    /**
     * Create Instant session if enabled (experimental)
     *
     * @return bool|string
     */
    public function createInstantSession()
    {
        // If cookie forwarding is enabled, then generate sessionId, retrieve cookies and make call to Instant.
        if ($this->instantHelper->getCookieForwardingEnabled()) {
            $instantSessionId = $this->instantHelper->guid();
            $data['sessId'] = $instantSessionId;

            $cookieStr = '';
            foreach ($_COOKIE as $cookieKey => $cookieValue) {
                $cookieStr = $cookieStr . $cookieKey . '=' . $this->instantHelper->encodeURIComponent($cookieValue) . '; ';
            }

            $payload = [
                'cookie' => substr(trim($cookieStr), 0, -1),
                'id' => $instantSessionId,
                'storeCode' => $this->storeManager->getStore()->getCode()
            ];
            $this->doRequest->execute('session', $payload, 'POST', -1, 0, false, false);
        }
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $this->createInstantSession();
        $result = $this->jsonResultFactory->create();

        $data['enableMinicartBtn'] = $this->instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $this->instantHelper->getInstantAppId();
        $data['cartId'] = $this->getInvisibleCartFromCart();
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

        $data['mcBtnCustomStyle'] = $this->instantHelper->getMcBtnCustomStyle();
        $data['mcBtnContainerCustomStyle'] = $this->instantHelper->getMcBtnContainerCustomStyle();
        $data['mcBtnHideOrStrike'] = $this->instantHelper->getMcBtnShouldHideOrStrike();

        $data['cindexBtnCustomStyle'] = $this->instantHelper->getCindexBtnCustomStyle();
        $data['cindexBtnContainerCustomStyle'] = $this->instantHelper->getCindexBtnContainerCustomStyle();
        $data['cindexBtnHideOrStrike'] = $this->instantHelper->getCindexBtnShouldHideOrStrike();

        $data['cpageBtnCustomStyle'] = $this->instantHelper->getCpageBtnCustomStyle();
        $data['cpageBtnContainerCustomStyle'] = $this->instantHelper->getCpageBtnContainerCustomStyle();
        $data['cpageBtnHideOrStrike'] = $this->instantHelper->getCpageBtnShouldHideOrStrike();

        $data['gaVersion'] = $this->instantHelper->getGoogleAnalyticsVersion();
        $data['gaId'] = $this->instantHelper->getGoogleAnalyticsId();

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
        return $result;
    }
}
