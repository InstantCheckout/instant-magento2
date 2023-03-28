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

use Magento\Framework\Registry;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Backend\Block\Template\Context;

class PdpBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var JsonFactory
     */
    protected $registry;

    /**
     * @var JsonFactory
     */
    private $instantHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        InstantHelper $instantHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->instantHelper = $instantHelper;

        parent::__construct($context, $data);
    }

    public function getProductHasCustomOptions()
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customOptions = $_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($this->getProduct());
        return $customOptions->getSize() > 0;
    }

    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function _toHtml()
    {
        $catalogPageBtnEnabled = $this->instantHelper->getInstantBtnCatalogPageEnabled();
        $disabledForCustomerGroup = $this->instantHelper->getDisabledForCustomerGroup();

        if ($catalogPageBtnEnabled && !$disabledForCustomerGroup) {
            return parent::_toHtml();
        }

        return '';
    }
}
