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

        $paymentMethodEnabled = $this->instantHelper->getInstantAppId() !== '' && $this->instantHelper->getInstantAppId() !== null && $this->instantPayHelper->getInstantPayEnabled();
        $verificationElementEnabled = $this->instantHelper->getInstantAppId() !== '' && $this->instantHelper->getInstantAppId() !== null && $this->instantPayHelper->getVerificationElementEnabled();
        $bannerElementEnabled = $this->instantHelper->getInstantAppId() !== '' && $this->instantHelper->getInstantAppId() !== null && $this->instantPayHelper->getBannerElementEnabled();

        if ($paymentMethodEnabled) {
            $verificationElementEnabled = true;
            $bannerElementEnabled = true;
        }

        $data = [
            'payment' => [
                'instant' => [
                    'enabled' => $paymentMethodEnabled,
                    'bannerElementEnabled' => $bannerElementEnabled,
                    'verificationElementEnabled' => $verificationElementEnabled,
                    'merchantId' => $this->instantHelper->getInstantAppId(),
                    'storeCode' => $this->instantHelper->getStoreCode(),
                    'cartId' => $this->instantHelper->getSessionCartId(),
                    'verificationElement' => [
                        'emailFieldSelector' => $this->instantPayHelper->getVerificationElementEmailFieldSelector(),
                    ],
                    'bannerElement' => [
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
