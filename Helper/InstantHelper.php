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

namespace Instant\Checkout\Helper;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManager;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForCustomer;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForGuest;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class InstantHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const INSTANT_CHECKOUT_REQUESTLOG_TABLE = 'instant_checkout_requestlog';
    const INSTANT_APP_ID_PATH = 'instant/general/app_id';
    const ACCESS_TOKEN_PATH = 'instant/general/api_access_token';
    const ENABLE_INSTANT_SANDBOX_MODE_PATH = 'instant/general/enable_sandbox';
    const DISABLED_FOR_SKUS_CONTAINING = 'instant/general/disabled_for_skus_containing';
    const DISABLED_FOR_CUSTOMER_GROUP_IDS = 'instant/general/disabled_for_customer_group_ids';
    const AUTO_CONVERT_GUEST_TO_CUSTOMER = 'instant/general/auto_convert_guest_to_customer';
    const ENABLE_MULTICURRENCY_ON_SINGLE_STORE = 'instant/general/enable_multicurrency_on_single_store';
    const ENABLE_INSTANT_CATALOG_PAGE_PATH = 'instant/general/enable_catalog';
    const ENABLE_INSTANT_MINICART_BTN_PATH = 'instant/general/enable_minicart';
    const ENABLE_INSTANT_CHECKOUT_SUMMARY = 'instant/general/enable_checkout_summary';
    const ENABLE_INSTANT_CHECKOUT_PAGE_PATH = 'instant/general/enable_checkout_page';
    const RETRY_FAILURES_COUNT = 'instant/general/retry_failures_count';
    const MC_BTN_WIDTH = 'instant/visual/mc_btn_width';
    const PDP_SHOULD_RESIZE_PDP_BTN = 'instant/pdpcustomisation/should_resize_pdp_btn';
    const PDP_REPOSITION_OR_STRIKE_ABOVE_BTN = 'instant/pdpcustomisation/pdp_reposition_or_strike_above_btn';
    const PDP_BTN_CUSTOM_STYLE = 'instant/pdpcustomisation/pdp_btn_custom_style';
    const PDP_BTN_CONTAINER_CUSTOM_STYLE = 'instant/pdpcustomisation/pdp_btn_container_custom_style';
    const PDP_BTN_REPOSITION_DIV = 'instant/pdpcustomisation/pdp_btn_reposition_div';
    const PDP_BTN_REPOSITION_WITHIN_DIV = 'instant/pdpcustomisation/pdp_btn_reposition_within_div';
    const PDP_SHOULD_POSITION_PDP_BELOW_ATC = 'instant/pdpcustomisation/should_position_pdp_below_atc';
    const MC_BTN_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_custom_style';
    const MC_BTN_CONTAINER_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_container_custom_style';
    const MC_BTN_HIDE_OR_STRIKE = 'instant/mccustomisation/mc_btn_hide_or_strike';
    const CINDEX_BTN_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_custom_style';
    const CINDEX_BTN_CONTAINER_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_container_custom_style';
    const CINDEX_SHOULD_RESIZE_BTN = 'instant/visual/should_resize_cart_index_btn';
    const CINDEX_BTN_HIDE_OR_STRIKE = 'instant/cindexcustomisation/cindex_btn_hide_or_strike';
    const TEST_TEST_TEST = 'instant/cindexcustomisation/test';

    const CONFIG_PATHS = [
        self::INSTANT_APP_ID_PATH => ['type' => 'string'],
        self::ACCESS_TOKEN_PATH => ['type' => 'string'],
        self::ENABLE_INSTANT_SANDBOX_MODE_PATH => ['type' => 'boolean'],
        self::DISABLED_FOR_SKUS_CONTAINING => ['type' => 'string'],
        self::DISABLED_FOR_CUSTOMER_GROUP_IDS => ['type' => 'string'],
        self::AUTO_CONVERT_GUEST_TO_CUSTOMER => ['type' => 'boolean'],
        self::ENABLE_MULTICURRENCY_ON_SINGLE_STORE => ['type' => 'boolean'],
        self::ENABLE_INSTANT_CATALOG_PAGE_PATH => ['type' => 'boolean'],
        self::ENABLE_INSTANT_MINICART_BTN_PATH => ['type' => 'boolean'],
        self::ENABLE_INSTANT_CHECKOUT_SUMMARY => ['type' => 'boolean'],
        self::ENABLE_INSTANT_CHECKOUT_PAGE_PATH => ['type' => 'boolean'],
        self::RETRY_FAILURES_COUNT => ['type' => 'integer'],
        self::MC_BTN_WIDTH => ['type' => 'string'],
        self::PDP_SHOULD_RESIZE_PDP_BTN => ['type' => 'boolean'],
        self::PDP_REPOSITION_OR_STRIKE_ABOVE_BTN => ['type' => 'boolean'],
        self::PDP_BTN_CUSTOM_STYLE => ['type' => 'string'],
        self::PDP_BTN_CONTAINER_CUSTOM_STYLE => ['type' => 'string'],
        self::PDP_BTN_REPOSITION_DIV => ['type' => 'string'],
        self::PDP_BTN_REPOSITION_WITHIN_DIV => ['type' => 'string'],
        self::PDP_SHOULD_POSITION_PDP_BELOW_ATC => ['type' => 'boolean'],
        self::MC_BTN_CUSTOM_STYLE => ['type' => 'string'],
        self::MC_BTN_CONTAINER_CUSTOM_STYLE => ['type' => 'string'],
        self::MC_BTN_HIDE_OR_STRIKE => ['type' => 'boolean'],
        self::CINDEX_BTN_CUSTOM_STYLE => ['type' => 'string'],
        self::CINDEX_BTN_CONTAINER_CUSTOM_STYLE => ['type' => 'string'],
        self::CINDEX_SHOULD_RESIZE_BTN => ['type' => 'boolean'],
        self::CINDEX_BTN_HIDE_OR_STRIKE => ['type' => 'boolean'],
    ];


    /**
     * @var CheckoutSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CreateEmptyCartForCustomer
     */
    private $createEmptyCartForCustomer;

    /**
     * @var CreateEmptyCartForGuest
     */
    private $createEmptyCartForGuest;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

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
     * */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        SessionManager $sessionManager,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        CreateEmptyCartForCustomer $createEmptyCartForCustomer,
        CreateEmptyCartForGuest $createEmptyCartForGuest,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CollectionFactory $collectionFactory,
        Manager $cacheManager,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        JsonFactory $jsonResultFactory,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->checkoutSession = $checkoutSession;
        $this->createEmptyCartForCustomer = $createEmptyCartForCustomer;
        $this->createEmptyCartForGuest = $createEmptyCartForGuest;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->cacheManager = $cacheManager;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;

        return parent::__construct($context);
    }

    /**
     * Check table exists or not
     *
     * @return bool
     */
    public function doesInstantRequestLogTableExist()
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName(self::INSTANT_CHECKOUT_REQUESTLOG_TABLE);

            $isTableExist = $connection->isTableExists($tableName);

            return $isTableExist;
        } catch (Exception $e) {
            return false;
        }
    }

    public function addCommentToOrder(Order $order, string $comment)
    {
        $commentToAdd = '[INSTANT] (Order ID: ' . $order->getId() . '): ' . sprintf($comment);
        $statusComment = NULL;

        try {
            $statusComment = $order->addCommentToStatusHistory($commentToAdd);
            $this->orderStatusRepository->save($statusComment);
            $this->logInfo($comment);
        } catch (Exception $e) {
            $order->addStatusHistoryComment($commentToAdd);
            $order->save();
        }
    }

    public function logInfo(string $log)
    {
        $this->logger->info($log);
    }

    public function getConfigField($key)
    {
        // Retrieve the field type from the $configFields array.
        $fieldType = isset (self::CONFIG_PATHS[$key]) ? self::CONFIG_PATHS[$key]['type'] : 'string';

        // Get the value from the configuration.
        $fieldValue = $this->scopeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Convert the value based on the type.
        if ($fieldType === 'boolean') {
            // Explicitly check for '0' or '1' for boolean fields.
            return $fieldValue === "1";
        } else {
            // For non-boolean fields, return the value as is.
            return $fieldValue;
        }
    }


    public function getCustomerGroupId()
    {
        $customerGroupId = -1;
        if ($this->customerSession->isLoggedIn()) {
            $customerGroupId = $this->customerSession->getCustomer()->getGroupId();
        }
        return $customerGroupId;
    }

    public function getDisabledForCustomerGroup()
    {
        $disabledForCustomerGroupIdsConfig = $this->getConfigField(self::DISABLED_FOR_CUSTOMER_GROUP_IDS);
        $disabledCustomerGroupIds = explode(',', $disabledForCustomerGroupIdsConfig ?? '');
        $customerGroupId = $this->getCustomerGroupId();

        return in_array($customerGroupId, $disabledCustomerGroupIds);
    }


    public function getInstantApiUrl()
    {
        $apiUrl = 'api.instant.one/';
        $isStaging = $this->getConfigField(self::ENABLE_INSTANT_SANDBOX_MODE_PATH);

        if ($isStaging) {
            $apiUrl = 'staging.' . $apiUrl;
        }

        return "https://" . $apiUrl;
    }

    public function createGuid()
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(16384, 20479),
            random_int(32768, 49151),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535)
        );
    }

    public function getSessionCartId()
    {
        try {
            $cartId = $this->checkoutSession->getQuote()->getEntityId();

            if (empty ($cartId)) {
                $customerId = -1;
                if ($this->customerSession->isLoggedIn()) {
                    $customerId = $this->customerSession->getId();
                }

                $customerLoggedIn = $customerId && $customerId > -1;

                $maskedQuoteId = $customerLoggedIn
                    ? $this->createEmptyCartForCustomer->execute($customerId)
                    : $this->createEmptyCartForGuest->execute();
                $cartId = $this->quoteIdMaskFactory->create()->load($maskedQuoteId, 'masked_id')->getQuoteId();

                if (!$customerLoggedIn) {
                    $this->checkoutSession->setQuoteId($cartId);
                }
            }

            return $cartId;
        } catch (Exception $e) {
            $this->logger->error("Exception raised in Instant/Checkout/Controller/Data/GetConfig");
            $this->logger->error($e->getMessage());
            return '';
        }
    }

    public function getUncachedCoreConfigValue($pathFieldValue)
    {
        $collection = $this->collectionFactory->create();
        $configValue = $collection->addFieldToFilter('path', ['eq' => $pathFieldValue])->getFirstItem()->getValue();

        return $configValue ?? "";
    }


    public function getInstantOrderParam($order, $type)
    {
        $paramData = '';
        foreach ($order->getStatusHistoryCollection() as $status) {
            $comment = $status->getComment();
            if ($comment && strpos($comment, 'INSTANT_PARAMS') !== false) {
                $paramData = $this->extractInstantParamsData($comment, $type);
            }
        }
        return $paramData;
    }

    private function extractInstantParamsData($comment, $type)
    {
        $pattern = '/(\[INSTANT_PARAMS\]): (\[\{.*\}\])/';
        $matches = [];
        preg_match($pattern, $comment, $matches);

        if (count($matches) < 3 || !is_string($matches[2])) {
            return '';
        }

        $match = $matches[2];

        if (empty ($match)) {
            return '';
        }

        $array = json_decode($match, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON decoding errors in some way, e.g. logging or throwing an exception
            $array = array();
        }

        foreach ($array as $item) {
            if (isset ($item['type']) && $item['type'] === $type) {
                return isset ($item['data']) ? $item['data'] : '';
            }
        }

        return '';
    }

    /**
     * Execute function
     */
    public function getInstantConfig()
    {
        $data = [];

        /* General */
        $data['appId'] = $this->getConfigField(self::INSTANT_APP_ID_PATH);
        $data['storeCode'] = $this->storeManager->getStore()->getCode();

        $data['cartId'] = $this->getSessionCartId();
        $data['enableSandbox'] = $this->getConfigField(self::ENABLE_INSTANT_SANDBOX_MODE_PATH);
        $data['disabledForSkusContaining'] = explode(',', $this->getConfigField(self::DISABLED_FOR_SKUS_CONTAINING) ?? '');
        $data['disabledForCustomerGroup'] = $this->getDisabledForCustomerGroup();
        $data['customerGroupId'] = $this->getCustomerGroupId();

        $data['currentCurrencyCode'] = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $data['baseCurrencyCode'] = $this->storeManager->getStore()->getBaseCurrencyCode();

        $data['enablePdpBtn'] = $this->getConfigField(self::ENABLE_INSTANT_CATALOG_PAGE_PATH);
        $data['enableMinicartBtn'] = $this->getConfigField(self::ENABLE_INSTANT_MINICART_BTN_PATH);
        $data['enableCindexBtn'] = $this->getConfigField(self::ENABLE_INSTANT_CHECKOUT_SUMMARY);
        $data['enableCheckoutPage'] = $this->getConfigField(self::ENABLE_INSTANT_CHECKOUT_PAGE_PATH);

        $data['shouldResizePdpBtn'] = $this->getConfigField(self::PDP_SHOULD_RESIZE_PDP_BTN);
        $data['shouldResizeCartIndexBtn'] = $this->getConfigField(self::CINDEX_SHOULD_RESIZE_BTN);
        $data['shouldPositionPdpBelowAtc'] = $this->getConfigField(self::PDP_SHOULD_POSITION_PDP_BELOW_ATC);
        $data['pdpBtnCustomStyle'] = $this->getConfigField(self::PDP_BTN_CUSTOM_STYLE);
        $data['pdpBtnContainerCustomStyle'] = $this->getConfigField(self::PDP_BTN_CONTAINER_CUSTOM_STYLE);
        $data['pdpBtnRepositionDiv'] = $this->getConfigField(self::PDP_BTN_REPOSITION_DIV);
        $data['pdpBtnRepositionWithinDiv'] = $this->getConfigField(self::PDP_BTN_REPOSITION_WITHIN_DIV);
        $data['pdpShouldRepositionOrStrikeAboveBtn'] = $this->getConfigField(self::PDP_REPOSITION_OR_STRIKE_ABOVE_BTN);

        $data['mcBtnWidth'] = $this->getConfigField(self::MC_BTN_WIDTH);
        $data['mcBtnCustomStyle'] = $this->getConfigField(self::MC_BTN_CUSTOM_STYLE);
        $data['mcBtnContainerCustomStyle'] = $this->getConfigField(self::MC_BTN_CONTAINER_CUSTOM_STYLE);
        $data['mcBtnHideOrStrike'] = $this->getConfigField(self::MC_BTN_HIDE_OR_STRIKE);

        $data['cindexBtnCustomStyle'] = $this->getConfigField(self::CINDEX_BTN_CUSTOM_STYLE);
        $data['cindexBtnContainerCustomStyle'] = $this->getConfigField(self::CINDEX_BTN_CONTAINER_CUSTOM_STYLE);
        $data['cindexBtnHideOrStrike'] = $this->getConfigField(self::CINDEX_BTN_HIDE_OR_STRIKE);

        $data['enableMulticurrencyOnSingleStore'] = $this->getConfigField(self::ENABLE_MULTICURRENCY_ON_SINGLE_STORE);

        $sessionId = session_id();
        if (!empty ($sessionId)) {
            $data['sessionId'] = session_id();
        }

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerData()->getId());
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

        return $data;
    }
}
