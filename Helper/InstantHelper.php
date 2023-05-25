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
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForCustomer;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForGuest;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\Manager;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;

use function Safe\preg_match_all;

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
    const SHOULD_RESIZE_CART_INDEX_BTN = 'instant/visual/should_resize_cart_index_btn';

    const CPAGE_BTN_WIDTH = 'instant/visual/cpage_btn_width';

    const PDP_SHOULD_RESIZE_PDP_BTN = 'instant/pdpcustomisation/should_resize_pdp_btn';
    const PDP_REPOSITION_OR_STRIKE_ABOVE_BTN = 'instant/pdpcustomisation/pdp_reposition_or_strike_above_btn';
    const PDP_BTN_CUSTOM_STYLE = 'instant/pdpcustomisation/pdp_btn_custom_style';
    const PDP_BTN_CONTAINER_CUSTOM_STYLE = 'instant/pdpcustomisation/pdp_btn_container_custom_style';
    const PDP_BTN_REPOSITION_DIV = 'instant/pdpcustomisation/pdp_btn_reposition_div';
    const PDP_BTN_REPOSITION_WITHIN_DIV = 'instant/pdpcustomisation/pdp_btn_reposition_within_div';
    const PDP_SHOULD_POSITION_PDP_BELOW_ATC = 'instant/pdpcustomisation/should_position_pdp_below_atc';

    const STB_ENABLED = 'instant/stb_visual_customisation/stb_enabled';
    const STB_HEIGHT = 'instant/stb_visual_customisation/stb_height';
    const STB_BORDER_RADIUS = 'instant/stb_visual_customisation/stb_border_radius';
    const STB_BOTTOM_LAYER_BORDER_COLOUR = 'instant/stb_visual_customisation/stb_bottom_layer_border_colour';
    const STB_BOTTOM_LAYER_BACKGROUND_COLOUR = 'instant/stb_visual_customisation/stb_bottom_layer_background_colour';
    const STB_TOP_LAYER_BACKGROUND_COLOUR = 'instant/stb_visual_customisation/stb_top_layer_background_colour';
    const STB_TOP_LAYER_TEXT_COLOUR = 'instant/stb_visual_customisation/stb_top_layer_text_colour';
    const STB_BOTTOM_LAYER_TEXT_COLOUR = 'instant/stb_visual_customisation/stb_bottom_layer_text_colour';
    const STB_THUMB_BACKGROUND_COLOUR = 'instant/stb_visual_customisation/stb_thumb_background_colour';
    const STB_FONT_FAMILY = 'instant/stb_visual_customisation/stb_font_family';
    const STB_TOP_LAYER_FONT_SIZE = 'instant/stb_visual_customisation/stb_top_layer_font_size';
    const STB_BOTTOM_LAYER_FONT_SIZE = 'instant/stb_visual_customisation/stb_bottom_layer_font_size';
    const STB_FONT_WEIGHT = 'instant/stb_visual_customisation/stb_font_weight';

    const MC_BTN_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_custom_style';
    const MC_BTN_CONTAINER_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_container_custom_style';
    const MC_BTN_HIDE_OR_STRIKE = 'instant/mccustomisation/mc_btn_hide_or_strike';

    const CINDEX_BTN_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_custom_style';
    const CINDEX_BTN_CONTAINER_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_container_custom_style';
    const CINDEX_BTN_HIDE_OR_STRIKE = 'instant/cindexcustomisation/cindex_btn_hide_or_strike';

    const CPAGE_BTN_CUSTOM_STYLE = 'instant/cpagecustomisation/cpage_btn_custom_style';
    const CPAGE_BTN_CONTAINER_CUSTOM_STYLE = 'instant/cpagecustomisation/cpage_btn_container_custom_style';
    const CPAGE_BTN_HIDE_OR_STRIKE = 'instant/cpagecustomisation/cpage_btn_hide_or_strike';

    const ORDER_PARAM_SESSION_ID = "SESSION_ID";

    /**
     * @var \Magento\Framework\SessionSessionManager
     */
    private $sessionManager;

    /**
     * @var Session
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
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * Constructor.
     * @param Context $context
     * @param Session $customerSession
     * */
    public function __construct(
        Context $context,
        Session $customerSession,
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
        OrderStatusHistoryRepositoryInterface $orderStatusRepository
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
            $connection  = $this->resourceConnection->getConnection();
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

    public function getConfig($config)
    {
        return $this->scopeConfig->getValue(
            $config,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSessionId()
    {
        return $this->sessionManager->getSessionId();
    }

    public function getRetryFailuresCount()
    {
        $retryFailuresCount = $this->getConfig(self::RETRY_FAILURES_COUNT);
        return $retryFailuresCount;
    }

    public function getInstantAppId()
    {
        $instantAppId = $this->getConfig(self::INSTANT_APP_ID_PATH);
        return $instantAppId;
    }

    public function getInstantApiAccessToken()
    {
        $instantAccessToken = $this->getConfig(self::ACCESS_TOKEN_PATH);
        return $instantAccessToken;
    }

    public function getShouldAutoConvertGuestToCustomer()
    {
        $shouldConvert = $this->getConfig(self::AUTO_CONVERT_GUEST_TO_CUSTOMER);
        return $shouldConvert === "1";
    }

    public function getInstantMinicartBtnEnabled()
    {
        $minicartBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_MINICART_BTN_PATH);
        return $minicartBtnEnabled === "1";
    }

    public function getInstantBtnCheckoutPageEnabled()
    {
        $checkoutPageBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_CHECKOUT_PAGE_PATH);
        return $checkoutPageBtnEnabled === "1";
    }

    public function getInstantBtnCheckoutSummaryEnabled()
    {
        $checkoutSummaryEnabled = $this->getConfig(self::ENABLE_INSTANT_CHECKOUT_SUMMARY);
        return $checkoutSummaryEnabled === "1";
    }

    public function getEnableMulticurrencyOnSingleStore()
    {
        $enableMultiCurrencyOnSingleStore = $this->getConfig(self::ENABLE_MULTICURRENCY_ON_SINGLE_STORE);
        return $enableMultiCurrencyOnSingleStore === "1";
    }

    public function getInstantBtnCatalogPageEnabled()
    {
        $catalogPageBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_CATALOG_PAGE_PATH);
        return $catalogPageBtnEnabled === "1";
    }

    public function getSandboxEnabledConfig()
    {
        $sandboxEnabled = $this->getConfig(self::ENABLE_INSTANT_SANDBOX_MODE_PATH);
        return $sandboxEnabled === "1";
    }

    public function getDisabledForSkusContaining()
    {
        $disableForSkusContaining = $this->getConfig(self::DISABLED_FOR_SKUS_CONTAINING);
        return explode(',', $disableForSkusContaining ?? '');
    }

    public function getDisabledForCustomerGroup()
    {
        $disabledForCustomerGroupIdsConfig = $this->getConfig(self::DISABLED_FOR_CUSTOMER_GROUP_IDS);

        $disabledCustomerGroupIds = explode(',', $disabledForCustomerGroupIdsConfig ?? '');
        $customerGroupId = $this->getCustomerGroupId();

        return in_array($customerGroupId, $disabledCustomerGroupIds);
    }

    public function getCustomerGroupId()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getGroupId();
        }

        return -1;
    }

    public function getCustomerId()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerData()->getId();
        }

        return -1;
    }

    public function getMcBtnWidth()
    {
        return $this->getConfig(self::MC_BTN_WIDTH);
    }

    public function getShouldResizeCartIndexBtn()
    {
        $shouldResize = $this->getConfig(self::SHOULD_RESIZE_CART_INDEX_BTN);
        return $shouldResize === '1';
    }

    public function getCPageBtnWidth()
    {
        return $this->getConfig(self::CPAGE_BTN_WIDTH);
    }

    public function getShouldResizePdpBtn()
    {
        $shouldResize = $this->getConfig(self::PDP_SHOULD_RESIZE_PDP_BTN);
        return $shouldResize === "1";
    }

    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getShouldPositionPdpBelowAtc()
    {
        $shouldPosition = $this->getConfig(self::PDP_SHOULD_POSITION_PDP_BELOW_ATC);
        return $shouldPosition === "1";
    }

    public function getShouldRepositionOrStrikeAbovePdpBtn()
    {
        $shouldRepositionOrStrikeAbovePdpBtn = $this->getConfig(self::PDP_REPOSITION_OR_STRIKE_ABOVE_BTN);
        return $shouldRepositionOrStrikeAbovePdpBtn === "1";
    }

    public function getPdpBtnCustomStyle()
    {
        $pdpBtnCustomStyle = $this->getConfig(self::PDP_BTN_CUSTOM_STYLE);
        return $pdpBtnCustomStyle;
    }

    public function getPdpBtnContainerCustomStyle()
    {
        $pdpBtnContainerCustomStyle = $this->getConfig(self::PDP_BTN_CONTAINER_CUSTOM_STYLE);
        return $pdpBtnContainerCustomStyle;
    }

    public function getPdpBtnRepositionDiv()
    {
        $pdpBtnRepositionDiv = $this->getConfig(self::PDP_BTN_REPOSITION_DIV);
        return $pdpBtnRepositionDiv;
    }

    public function getPdpBtnRepositionWithinDiv()
    {
        $pdpBtnRepositionWithinDiv = $this->getConfig(self::PDP_BTN_REPOSITION_WITHIN_DIV);
        return $pdpBtnRepositionWithinDiv;
    }


    public function getStbEnabled()
    {
        $stbEnabled = $this->getConfig(self::STB_ENABLED);
        return $stbEnabled === '1';
    }

    public function getStbHeight()
    {
        $stbHeight = $this->getConfig(self::STB_HEIGHT);
        return $stbHeight;
    }

    public function getStbBorderRadius()
    {
        $stbBorderRadius = $this->getConfig(self::STB_BORDER_RADIUS);
        return $stbBorderRadius;
    }

    public function getStbBottomLayerBorderColour()
    {
        $stbBottomLayerBorderColour = $this->getConfig(self::STB_BOTTOM_LAYER_BORDER_COLOUR);
        return $stbBottomLayerBorderColour;
    }

    public function getStbBottomLayerBackgroundColour()
    {
        $stbBottomLayerBackgroundColour = $this->getConfig(self::STB_BOTTOM_LAYER_BACKGROUND_COLOUR);
        return $stbBottomLayerBackgroundColour;
    }

    public function getStbTopLayerBackgroundColour()
    {
        $stbTopLayerBackgroundColour = $this->getConfig(self::STB_TOP_LAYER_BACKGROUND_COLOUR);
        return $stbTopLayerBackgroundColour;
    }

    public function getStbTopLayerTextColour()
    {
        $stbTopLayerTextColour = $this->getConfig(self::STB_TOP_LAYER_TEXT_COLOUR);
        return $stbTopLayerTextColour;
    }

    public function getStbBottomLayerTextColour()
    {
        $stbBottomLayerTextColour = $this->getConfig(self::STB_BOTTOM_LAYER_TEXT_COLOUR);
        return $stbBottomLayerTextColour;
    }

    public function getStbThumbBackgroundColour()
    {
        $stbThumbBackgroundColour = $this->getConfig(self::STB_THUMB_BACKGROUND_COLOUR);
        return $stbThumbBackgroundColour;
    }

    public function getStbFontFamily()
    {
        $stbFontFamily = $this->getConfig(self::STB_FONT_FAMILY);
        return $stbFontFamily;
    }

    public function getStbFontWeight()
    {
        $stbFontWeight = $this->getConfig(self::STB_FONT_WEIGHT);
        return $stbFontWeight;
    }

    public function getStbTopLayerFontSize()
    {
        $stbTopLayerFontSize = $this->getConfig(self::STB_TOP_LAYER_FONT_SIZE);
        return $stbTopLayerFontSize;
    }

    public function getStbBottomLayerFontSize()
    {
        $stbBottomLayerFontSize = $this->getConfig(self::STB_BOTTOM_LAYER_FONT_SIZE);
        return $stbBottomLayerFontSize;
    }


    public function getMcBtnCustomStyle()
    {
        $mcBtnCustomStyle = $this->getConfig(self::MC_BTN_CUSTOM_STYLE);
        return $mcBtnCustomStyle;
    }

    public function getMcBtnContainerCustomStyle()
    {
        $mcBtnContainerCustomStyle = $this->getConfig(self::MC_BTN_CONTAINER_CUSTOM_STYLE);
        return $mcBtnContainerCustomStyle;
    }

    public function getMcBtnShouldHideOrStrike()
    {
        $mcBtnContainerCustomStyle = $this->getConfig(self::MC_BTN_HIDE_OR_STRIKE);
        return $mcBtnContainerCustomStyle === "1";
    }

    public function getCindexBtnCustomStyle()
    {
        $cIndexBtnCustomStyle = $this->getConfig(self::CINDEX_BTN_CUSTOM_STYLE);
        return $cIndexBtnCustomStyle;
    }

    public function getCindexBtnContainerCustomStyle()
    {
        $cIndexBtnContainerCustomStyle = $this->getConfig(self::CINDEX_BTN_CONTAINER_CUSTOM_STYLE);
        return $cIndexBtnContainerCustomStyle;
    }

    public function getCindexBtnShouldHideOrStrike()
    {
        $cIndexBtnHideOrStrike = $this->getConfig(self::CINDEX_BTN_HIDE_OR_STRIKE);
        return $cIndexBtnHideOrStrike === "1";
    }

    public function getCpageBtnCustomStyle()
    {
        $cPageBtnCustomStyle = $this->getConfig(self::CPAGE_BTN_CUSTOM_STYLE);
        return $cPageBtnCustomStyle;
    }

    public function getCpageBtnContainerCustomStyle()
    {
        $cPageBtnContainerCustomStyle = $this->getConfig(self::CPAGE_BTN_CONTAINER_CUSTOM_STYLE);
        return $cPageBtnContainerCustomStyle;
    }

    public function getCpageBtnShouldHideOrStrike()
    {
        $cPageBtnHideOrStrike = $this->getConfig(self::CPAGE_BTN_HIDE_OR_STRIKE);
        return $cPageBtnHideOrStrike === "1";
    }

    public function getInstantApiUrl()
    {
        $apiUrl = 'api.instant.one/';
        $isStaging = $this->getSandboxEnabledConfig();

        if ($isStaging) {
            $apiUrl = 'staging.' . $apiUrl;
        }

        return "https://" . $apiUrl;
    }

    public function guid()
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

    public function encodeURIComponent($str)
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }

    public function getSessionCartId()
    {
        try {
            $cartId = $this->checkoutSession->getQuote()->getEntityId();

            if (empty($cartId)) {
                $customerId = $this->getCustomerId();
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

    public function clearCache()
    {
        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
        $this->logger->info('Cache was cleared!');
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

        if (empty($match)) {
            return '';
        }

        $array = $this->jsonDecode($match);

        foreach ($array as $item) {
            if (isset($item['type']) && $item['type'] === $type) {
                return isset($item['data']) ? $item['data'] : '';
            }
        }

        return '';
    }

    private function jsonDecode($jsonString)
    {
        $result = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON decoding errors in some way, e.g. logging or throwing an exception
            return array();
        }

        return $result;
    }
}
