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

use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManager;
use Magento\Store\Model\StoreManagerInterface;

class InstantHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const INSTANT_APP_ID_PATH = 'instant/general/app_id';
    const ACCESS_TOKEN_PATH = 'instant/general/api_access_token';
    const ENABLE_INSTANT_CHECKOUT_PAGE_PATH = 'instant/general/enable_checkout_page';
    const ENABLE_INSTANT_MINICART_BTN_PATH = 'instant/general/enable_minicart';
    const ENABLE_INSTANT_SANDBOX_MODE_PATH = 'instant/general/enable_sandbox';
    const ENABLE_INSTANT_CATALOG_PAGE_PATH = 'instant/general/enable_catalog';
    const ENABLE_INSTANT_CHECKOUT_SUMMARY = 'instant/general/enable_checkout_summary';
    const RETRY_FAILURES_COUNT = 'instant/general/retry_failures_count';
    const ENABLE_COOKIE_FORWARDING = 'instant/general/enable_cookie_forwarding';
    const DISABLED_FOR_SKUS_CONTAINING = 'instant/general/disabled_for_skus_containing';
    const MC_BTN_WIDTH = 'instant/visual/mc_btn_width';
    const SHOULD_RESIZE_CART_INDEX_BTN = 'instant/visual/should_resize_cart_index_btn';
    const CPAGE_BTN_WIDTH = 'instant/visual/cpage_btn_width';
    const SHOULD_POSITION_PDP_BELOW_ATC = 'instant/visual/should_position_pdp_below_atc';
    const SHOULD_RESIZE_PDP_BTN = 'instant/visual/should_resize_pdp_btn';
    const BTN_BORDER_RADIUS = 'instant/visual/btn_border_radius';
    const BTN_HEIGHT = 'instant/visual/btn_height';
    const BTN_COLOR = 'instant/visual/btn_color';

    /**
     * @var \Magento\Framework\SessionSessionManager
     */
    private $sessionManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    /**
     * Constructor.
     * @param Context $context
     * @param Session $customerSession
     * */
    public function __construct(
        Context $context,
        Session $customerSession,
        SessionManager $sessionManager,
        StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->storeManager = $storeManager;

        return parent::__construct($context);
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

    public function getBtnColor()
    {
        $btnColor = $this->getConfig(self::BTN_COLOR);
        return $btnColor;
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

    public function getInstantBtnCatalogPageEnabled()
    {
        $catalogPageBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_CATALOG_PAGE_PATH);
        return $catalogPageBtnEnabled === "1";
    }

    public function getCookieForwardingEnabled()
    {
        $cookieForwardingEnabled = $this->getConfig(self::ENABLE_COOKIE_FORWARDING);
        return $cookieForwardingEnabled === "1";
    }

    public function getSandboxEnabledConfig()
    {
        $sandboxEnabled = $this->getConfig(self::ENABLE_INSTANT_SANDBOX_MODE_PATH);
        return $sandboxEnabled === "1";
    }

    public function getDisabledForSkusContaining()
    {
        $disableForSkusContaining = $this->getConfig(self::DISABLED_FOR_SKUS_CONTAINING);
        return explode(',', $disableForSkusContaining);
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

    public function getBtnBorderRadius()
    {
        return $this->getConfig(self::BTN_BORDER_RADIUS);
    }

    public function getBtnHeight()
    {
        return $this->getConfig(self::BTN_HEIGHT);
    }

    public function getShouldResizePdpBtn()
    {
        $shouldResize = $this->getConfig(self::SHOULD_RESIZE_PDP_BTN);
        return $shouldResize === "1";
    }

    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getShouldPositionPdpBelowAtc()
    {
        $shouldPosition = $this->getConfig(self::SHOULD_POSITION_PDP_BELOW_ATC);
        return $shouldPosition === "1";
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
}