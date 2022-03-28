<?php

/**
 * Instant_Checkout
 *
 * @package   Instant_Checkout
 * @author    Instant <hello@instant.one>
 * @copyright 2022 Copyright Instant. https://www.instantcheckout.com.au/
 * @license   https://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      https://www.instantcheckout.com.au/
 */

namespace Instant\Checkout\Block;

class PdpBlock extends \Magento\Framework\View\Element\Template
{
    protected $_registry;
    private $instantHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Instant\Checkout\Helper\InstantHelper $instantHelper,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->instantHelper = $instantHelper;
        parent::__construct($context, $data);
    }

    public function getProduct()
    {
        return $this->_registry->registry('current_product');
    }

    public function _toHtml()
    {
        $catalogPageBtnEnabled = $this->instantHelper->getInstantBtnCatalogPageEnabled();

        if ($catalogPageBtnEnabled) {
            return parent::_toHtml();
        }

        return '';
    }
}
