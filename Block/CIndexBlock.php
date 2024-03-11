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

use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\View\Element\Template;

class CIndexBlock extends \Magento\Framework\View\Element\Template
{

    private $instantHelper;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param InstantHelper $instantHelper
     * @param array $data
     */
    public function __construct(Template\Context $context, InstantHelper $instantHelper, array $data = [])
    {
        $this->instantHelper = $instantHelper;
        parent::__construct($context, $data);
    }


    public function _toHtml()
    {
        return parent::_toHtml();
    }
}
