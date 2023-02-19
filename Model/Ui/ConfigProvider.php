<?php

namespace Instant\Checkout\Model\Ui;

use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Helper\InstantPayHelper;
use Magento\Checkout\Model\ConfigProviderInterface;

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


    public function __construct(
        InstantHelper $instantHelper,
        InstantPayHelper $instantPayHelper
    ) {
        $this->instantHelper = $instantHelper;
        $this->instantPayHelper = $instantPayHelper;
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
                    ]
                ]
            ]
        ];

        return $data;
    }
}
