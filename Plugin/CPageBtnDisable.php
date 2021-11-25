<?php

namespace Instant\Checkout\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Class CPageBtnDisable
 *
 * @package Magento\Checkout\Plugin
 */
class CPageBtnDisable
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
        $isGuest = $instantHelper->getIsGuest();

        if (!$enabled || !$isGuest) {
            $jsLayout['components']['checkout']['children']['cpage-btn']['componentDisabled'] = true;
        }

        return $jsLayout;
    }
}
