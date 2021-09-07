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
        $enable = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('instant/general/enable_checkout_page');

        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->log(100, print_r($jsLayout, true));

        if (!$enable) {
            $jsLayout['components']['checkout']['children']['steps']['children']['checkout-page-instant-btn']['componentDisabled'] = true;
        }

        return $jsLayout;
    }
}
