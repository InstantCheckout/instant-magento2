<?php

namespace Instant\Checkout\Model\Ui;

use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Helper\InstantPayHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var InstantHelper
     */
    private $instantHelper;
    /**
     * @var InstantPayHelper
     */
    private $instantPayHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        InstantHelper $instantHelper,
        InstantPayHelper $instantPayHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->instantHelper = $instantHelper;
        $this->instantPayHelper = $instantPayHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $data = [];

        $data = [
            'payment' => [
                'instant' => [
                    'enabled' => $this->instantPayHelper->getInstantPayEnabled(),
                    'merchantId' => $this->instantHelper->getInstantAppId(),
                    'storeCode' => $this->instantHelper->getStoreCode(),
                    'cartId' => $this->instantHelper->getSessionCartId(),
                    'verificationElement' => [
                        'enabled' => $this->instantPayHelper->getVerificationElementEnabled(),
                        'emailFieldSelector' => $this->instantPayHelper->getVerificationElementEmailFieldSelector(),
                    ],
                    'bannerElement' => [
                        'enabled' => $this->instantPayHelper->getBannerElementEnabled(),
                        'targetElementSelector' => $this->instantPayHelper->getBannerElementTargetElementSelector(),
                        'shouldAppendToElement' => $this->instantPayHelper->getBannerElementShouldAppendToElement(),
                        'theme' => $this->instantPayHelper->getBannerElementTheme(),
                    ],
                    'successUrl' => $this->storeManager->getStore()->getUrl('checkout/onepage/success/')
                ]
            ]
        ];

        return $data;
    }
}
