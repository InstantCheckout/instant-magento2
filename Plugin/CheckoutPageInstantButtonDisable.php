<?php

namespace Instant\Checkout\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Class CheckoutPageInstantButtonDisable
 *
 * @package Magento\Checkout\Plugin
 */
class CheckoutPageInstantButtonDisable
{
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param array $jsLayout
     *
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $processor,
        array $jsLayout
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Helper\Data::class);

        $enabled = $instantHelper->getInstantBtnCheckoutPageEnabled();

        if (!$enabled) {
            $jsLayout['components']['checkout']['children']['steps']['children']['checkout-page-instant-btn']['componentDisabled'] = true;
        }

        return $jsLayout;
    }
}
