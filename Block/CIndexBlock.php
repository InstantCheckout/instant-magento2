<?php

namespace Instant\Checkout\Block;

class CIndexBlock extends \Magento\Framework\View\Element\Template
{

    public function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Helper\Data::class);

        $isGuest = $instantHelper->getIsGuest();
        $checkoutSummaryEnabled = $instantHelper->getInstantBtnCheckoutSummaryEnabled();

        if ($isGuest && $checkoutSummaryEnabled) {
            return parent::_toHtml();
        }

        return '';
    }
}
