<?php

namespace Instant\Checkout\Block;

class CheckoutSummaryBlock extends \Magento\Framework\View\Element\Template
{

    public function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Helper\Data::class);

        $shouldShowInstantBtnForCurrentUser = $instantHelper->getShouldShowInstantBtnForCurrentUser();
        $checkoutSummaryEnabled = $instantHelper->getInstantBtnCheckoutSummaryEnabled();

        if ($shouldShowInstantBtnForCurrentUser && $checkoutSummaryEnabled) {
            return parent::_toHtml();
        }

        return '';
    }
}
