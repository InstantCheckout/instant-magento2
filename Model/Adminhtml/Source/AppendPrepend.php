<?php

namespace Instant\Checkout\Model\Adminhtml\Source;

class AppendPrepend implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'append',
                'label' => __('Append to HTML element')
            ],
            [
                'value' => 'prepend',
                'label' => __('Prepend to HTML element')
            ],
        ];
    }
}
