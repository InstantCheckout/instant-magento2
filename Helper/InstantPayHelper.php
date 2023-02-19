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
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;

class InstantPayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const INSTANT_CHECKOUT_REQUESTLOG_TABLE = 'instant_checkout_requestlog';

    const INSTANT_APP_ID_PATH = 'instant/general/app_id';
    const ACCESS_TOKEN_PATH = 'instant/general/api_access_token';
    const ENABLE_INSTANT_SANDBOX_MODE_PATH = 'instant/general/enable_sandbox';
    const DISABLED_FOR_SKUS_CONTAINING = 'instant/general/disabled_for_skus_containing';
    const DISABLED_FOR_CUSTOMER_GROUP_IDS = 'instant/general/disabled_for_customer_group_ids';

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

    const MC_BTN_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_custom_style';
    const MC_BTN_CONTAINER_CUSTOM_STYLE = 'instant/mccustomisation/mc_btn_container_custom_style';
    const MC_BTN_HIDE_OR_STRIKE = 'instant/mccustomisation/mc_btn_hide_or_strike';

    const CINDEX_BTN_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_custom_style';
    const CINDEX_BTN_CONTAINER_CUSTOM_STYLE = 'instant/cindexcustomisation/cindex_btn_container_custom_style';
    const CINDEX_BTN_HIDE_OR_STRIKE = 'instant/cindexcustomisation/cindex_btn_hide_or_strike';

    const CPAGE_BTN_CUSTOM_STYLE = 'instant/cpagecustomisation/cpage_btn_custom_style';
    const CPAGE_BTN_CONTAINER_CUSTOM_STYLE = 'instant/cpagecustomisation/cpage_btn_container_custom_style';
    const CPAGE_BTN_HIDE_OR_STRIKE = 'instant/cpagecustomisation/cpage_btn_hide_or_strike';

    const GOOGLE_ANALYTICS_VERSION = 'instant/google/ga_version';
    const GOOGLE_ANALYTICS_ID = 'instant/google/ga_id';

    const INSTANT_PAY_VERIFICATION_ELEMENT_EMAIL_FIELD_SELECTOR = 'payment/instantpay/verificationElementEmailFieldSelector';

    /**
     * @var \Magento\Framework\SessionSessionManager
     */
    private $sessionManager;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InstantHelper
     */
    private $instantHelper;

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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        InstantHelper $instantHelper
    ) {
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->checkoutSession = $checkoutSession;
        $this->createEmptyCartForCustomer = $createEmptyCartForCustomer;
        $this->createEmptyCartForGuest = $createEmptyCartForGuest;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->instantHelper = $instantHelper;

        return parent::__construct($context);
    }

    public function getConfig($section, $field)
    {
        if (empty($storeId))
            $storeId = $this->instantHelper->getStoreId();


        $data = $this->scopeConfig->getValue("payment/instant_pay/$section" . "_" . $field, ScopeInterface::SCOPE_STORE, $storeId);

        return $data;
    }

    public function getGeneralConfig($field)
    {
        return $this->getConfig('general', $field);
    }

    public function getVerificationElementConfig($field)
    {
        return $this->getConfig('verificationElement', $field);
    }

    public function getBannerElementConfig($field)
    {
        return $this->getConfig('bannerElement', $field);
    }

    public function getVerificationElementEmailFieldSelector()
    {
        return $this->getVerificationElementConfig('emailFieldSelector');
    }

    public function getBannerElementTargetElementSelector()
    {
        return $this->getBannerElementConfig('targetElementSelector');
    }

    public function getBannerElementShouldAppendToElement()
    {
        return $this->getBannerElementConfig('shouldAppendToElement') === 'append';
    }

    public function getBannerElementTheme()
    {
        return $this->getBannerElementConfig('theme');
    }
}
