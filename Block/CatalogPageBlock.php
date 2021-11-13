<?php

namespace Instant\Checkout\Block;

class CatalogPageBlock extends \Magento\Framework\View\Element\Template
{

    public function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Helper\Data::class);

        $shouldShowInstantBtnForCurrentUser = $instantHelper->getShouldShowInstantBtnForCurrentUser();
        $catalogPageBtnEnabled = $instantHelper->getInstantBtnCatalogPageEnabled();

        if ($shouldShowInstantBtnForCurrentUser && $catalogPageBtnEnabled) {
            return parent::_toHtml();
        }

        return '';
    }
}
