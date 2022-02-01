<?php

declare(strict_types=1);

namespace Instant\Checkout\Block\Adminhtml\System\Config\Activation;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Send
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Send extends Field
{
    /**
     * @var string
     */
    protected $_template = 'system/config/activation/send.phtml';

    /**
     * Unset scope
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope();

        return parent::render($element);
    }

    /**
     * Get the button content
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();
        $this->addData([
            'button_label' => $originalData['button_label'],
            'button_url' => $this->getUrl($originalData['button_url'], ['_current' => true]),
            'html_id' => $element->getHtmlId(),
        ]);

        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('instant/activation/send', ['_current' => true]);
    }
}
