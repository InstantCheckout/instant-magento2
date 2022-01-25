<?php

namespace Instant\Checkout\Helper;

use Magento\Framework\App\Helper\Context;
use \Magento\Customer\Model\Session;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Instant App ID Path
     */
    const INSTANT_APP_ID_PATH = 'instant/general/app_id';

    /**
     * Default addtocart button form id
     */
    const PRODUCT_ADDTOCART_FORM_ID = 'product_addtocart_form';

    /**
     * Addtocart button form id path
     */
    const PRODUCT_ADDTOCART_FORM_ID_PATH = 'instant/general/product_addtocart_form_id';

    /**
     * Enable checkout page button path
     */
    const ENABLE_INSTANT_CHECKOUT_PAGE_PATH = 'instant/general/enable_checkout_page';

    /**
     * Enable minicart button path
     */
    const ENABLE_INSTANT_MINICART_BTN_PATH = 'instant/general/enable_minicart';

    /**
     * Enable sandbox mode path
     */
    const ENABLE_INSTANT_SANDBOX_MODE_PATH = 'instant/general/enable_sandbox';

    /**
     * Enable catalog page path
     */
    const ENABLE_INSTANT_CATALOG_PAGE_PATH = 'instant/general/enable_catalog';

    /**
     * Enable cart summary path
     */
    const ENABLE_INSTANT_CHECKOUT_SUMMARY = 'instant/general/enable_checkout_summary';

    /**
     * Threshold for cart total where Instant should be disabled path
     */
    const DISABLED_CART_TOTAL_THRESHOLD = 'instant/general/disabled_total_threshold';

    /**
     * Disable Instant for skus containing path
     */
    const DISABLED_FOR_SKUS_CONTAINING = 'instant/general/disabled_for_skus_containing';

    /**
     * Instant minicart button width
     */
    const MC_BTN_WIDTH = 'instant/visual/mc_btn_width';

    /**
     * Instant cart index button width
     */
    const CINDEX_BTN_WIDTH = 'instant/visual/cindex_btn_width';

    /**
     * Instant checkout page width
     */
    const CPAGE_BTN_WIDTH = 'instant/visual/cpage_btn_width';

    /**
     * Instant pdp button width
     */
    const PDP_BTN_WIDTH = 'instant/visual/pdp_btn_width';

    /**
     * Instant btn border radius
     */
    const BTN_BORDER_RADIUS = 'instant/visual/btn_border_radius';

    /**
     * Instant btn height
     */
    const BTN_HEIGHT = 'instant/visual/btn_height';

    /**
     * @var \Magento\Checkout\Model\CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    private $sessionManager;

    /**
     * Constructor.
     * @param Context $context
     * @param Session $customerSession
     * */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->serializer = $serializerInterface ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\JsonHexTag::class);
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
     * Enable Instant minicart button configuration
     * @return string
     */
    public function getInstantMinicartBtnEnabled()
    {
        $minicartBtnEnabled = $this->getConfig(self::ENABLE_INSTANT_MINICART_BTN_PATH);
        return $minicartBtnEnabled === "1";
    }

    /**
     * Get product page addtocart form id
     * @return string
     */
    public function getProductPageAddToCartFormId()
    {
        $addToCartFormId = $this->getConfig(self::PRODUCT_ADDTOCART_FORM_ID_PATH);
        return $addToCartFormId ? $addToCartFormId : self::PRODUCT_ADDTOCART_FORM_ID;
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
     * Get Instant disabled cart total threshold
     * @return string
     */
    public function getDisabledCartTotalThreshold()
    {
        $threshold = $this->getConfig(self::DISABLED_CART_TOTAL_THRESHOLD);
        return $threshold;
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
     * @return string
     */
    public function getCIndexBtnWidth()
    {
        return $this->getConfig(self::CINDEX_BTN_WIDTH);
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
    public function getPdpBtnWidth()
    {
        return $this->getConfig(self::PDP_BTN_WIDTH);
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Retrieve serialized checkout config.
     *
     * @return bool|string
     * @since 100.2.0
     */
    public function getSerializedCheckoutConfig()
    {
        try {
            return  $this->serializer->serialize($this->getCheckoutConfig());
        } catch (\Exception $e) {
            return null;
        }
    }
}
