<?php

namespace Instant\Checkout\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Instant App ID Path
     */
    const INSTANT_APP_ID_PATH = 'instant/general/app_id';

    /**
     * Addtocart button form id
     */
    const PRODUCT_ADDTOCART_FORM_ID = 'product_addtocart_form';

    /**
     * Addtocart button form id path
     */
    const PRODUCT_ADDTOCART_FORM_ID_PATH = 'instant/general/product_addtocart_form_id';

    /**
     * Enable minicart button path
     */
    const ENABLE_INSTANT_MINICART_BTN_PATH = 'instant/general/enable_minicart';

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
        return $minicartBtnEnabled;
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
}
