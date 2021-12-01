<?php

namespace Instant\Checkout\Block;

class PdpBlock extends \Magento\Framework\View\Element\Template
{
    protected $_registry;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getProduct()
    {
        return $this->_registry->registry('current_product');
    }

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
