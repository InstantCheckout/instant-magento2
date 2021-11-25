<?php

namespace Instant\Checkout\Block;

class PdpBlock extends \Magento\Framework\View\Element\Template
{

    public function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Helper\Data::class);

        $isGuest = $instantHelper->getIsGuest();
        $catalogPageBtnEnabled = $instantHelper->getInstantBtnCatalogPageEnabled();

        if ($isGuest && $catalogPageBtnEnabled) {
            return parent::_toHtml();
        }

        return '';
    }
}
