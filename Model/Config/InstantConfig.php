<?php

namespace Instant\Checkout\Model\Config;

use Magento\Framework\App\Helper\Context;
use \Magento\Customer\Model\Session;

class InstantConfig extends \Magento\Framework\App\Helper\AbstractHelper
{
    const INSTANT_APP_ID_PATH = 'instant/general/app_id';
    const ACCESS_TOKEN_PATH = 'instant/general/api_access_token';
    const ENABLE_INSTANT_CHECKOUT_PAGE_PATH = 'instant/general/enable_checkout_page';
    const ENABLE_INSTANT_MINICART_BTN_PATH = 'instant/general/enable_minicart';
    const ENABLE_INSTANT_SANDBOX_MODE_PATH = 'instant/general/enable_sandbox';
    const ENABLE_INSTANT_CATALOG_PAGE_PATH = 'instant/general/enable_catalog';
    const ENABLE_INSTANT_CHECKOUT_SUMMARY = 'instant/general/enable_checkout_summary';
    const DISABLED_FOR_SKUS_CONTAINING = 'instant/general/disabled_for_skus_containing';
    const MC_BTN_WIDTH = 'instant/visual/mc_btn_width';
    const SHOULD_RESIZE_CART_INDEX_BTN = 'instant/visual/should_resize_cart_index_btn';
    const CPAGE_BTN_WIDTH = 'instant/visual/cpage_btn_width';
    const SHOULD_RESIZE_PDP_BTN = 'instant/visual/should_resize_pdp_btn';
    const BTN_BORDER_RADIUS = 'instant/visual/btn_border_radius';
    const BTN_HEIGHT = 'instant/visual/btn_height';

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    private $sessionManager;
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Constructor.
     * @param Context $context
     * @param Session $customerSession
     * */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Magento\Framework\Session\SessionManager $sessionManager
    ) {
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;

        return parent::__construct($context);
    }

    /**
     * Retrieve config value
     *
     * @return string
     */
    public function getConfig($config)
    {
        return $this->scopeConfig->getValue(
            $config,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve sessionID
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionManager->getSessionId();
    }

    /**
     * Get whether customer is logged in 
     */
    public function getIsGuest()
    {
        return !$this->customerSession->isLoggedIn();
    }

    /**
     * Get Instant App ID
     * @return string
     */
    public function getInstantAppId()
    {
        $instantAppId = $this->getConfig(self::INSTANT_APP_ID_PATH);
        return $instantAppId;
    }

    /**
     * Get Instant Access Token
     * @return string
     */
    public function getInstantApiAccessToken()
    {
        $instantAccessToken = $this->getConfig(self::ACCESS_TOKEN_PATH);
        return $instantAccessToken;
    }

    /**
     * Enable Instant minicart button configuration
     * @return string
     */
    public function getInstantMinicartBtnEnabled()
    {
        $minicartBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_MINICART_BTN_PATH);
        return $minicartBtnEnabled === "1";
    }

    /**
     * Get checkout page enabled
     * @return string
     */
    public function getInstantBtnCheckoutPageEnabled()
    {
        $checkoutPageBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_CHECKOUT_PAGE_PATH);
        return $checkoutPageBtnEnabled === "1";
    }

    /**
     * Get cart summary enabled
     * @return string
     */
    public function getInstantBtnCheckoutSummaryEnabled()
    {
        $checkoutSummaryEnabled = $this->getConfig(self::ENABLE_INSTANT_CHECKOUT_SUMMARY);
        return $checkoutSummaryEnabled === "1";
    }

    /**
     * Get catalog page enabled
     * @return string
     */
    public function getInstantBtnCatalogPageEnabled()
    {
        $catalogPageBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_CATALOG_PAGE_PATH);
        return $catalogPageBtnEnabled === "1";
    }

    /**
     * Get staging/sandbox or live/production environment config
     * @return string
     */
    public function getSandboxEnabledConfig()
    {
        $sandboxEnabled = $this->getConfig(self::ENABLE_INSTANT_SANDBOX_MODE_PATH);
        return $sandboxEnabled === "1";
    }

    /**
     * Get disabled SKU phrases
     * @return string
     */
    public function getDisabledForSkusContaining()
    {
        $disableForSkusContaining = $this->getConfig(self::DISABLED_FOR_SKUS_CONTAINING);
        return explode(',', $disableForSkusContaining);
    }

    /**
     * Get minicart btn width
     * @return string
     */
    public function getMcBtnWidth()
    {
        return $this->getConfig(self::MC_BTN_WIDTH);
    }

    /**
     * Get cart index btn width
     * @return boolean
     */
    public function getShouldResizeCartIndexBtn()
    {
        $shouldResize = $this->getConfig(self::SHOULD_RESIZE_CART_INDEX_BTN);
        return $shouldResize === '1';
    }

    /**
     * Get checkout page width
     * @return string
     */
    public function getCPageBtnWidth()
    {
        return $this->getConfig(self::CPAGE_BTN_WIDTH);
    }

    /**
     * Get btn border radius
     * @return string
     */
    public function getBtnBorderRadius()
    {
        return $this->getConfig(self::BTN_BORDER_RADIUS);
    }

    /**
     * Get btn height
     * @return string
     */
    public function getBtnHeight()
    {
        return $this->getConfig(self::BTN_HEIGHT);
    }

    /**
     * Get pdp btn width
     * @return string
     */
    public function getShouldResizePdpBtn()
    {
        $shouldResize = $this->getConfig(self::SHOULD_RESIZE_PDP_BTN);
        return $shouldResize === "1";
    }

    public function getInstantApiUrl()
    {
        return "https://zaonwy905l.execute-api.ap-southeast-2.amazonaws.com/pr289";
    }

    public function guid()
    {
        $guid = '';
        if (function_exists('com_create_guid') === true) {
            $guid = trim(com_create_guid(), '{}');
        } else {
            $guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }

        return strtolower($guid);
    }

    public function encodeURIComponent($str)
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }
}
