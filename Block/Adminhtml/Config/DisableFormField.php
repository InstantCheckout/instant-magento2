<?php

namespace Instant\Checkout\Block\Adminhtml\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class DisableFormField extends \Magento\Config\Block\System\Config\Form\Field
{    
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return $element->getElementHtml();
    }
}