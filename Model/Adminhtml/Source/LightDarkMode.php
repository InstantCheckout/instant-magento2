<?php

namespace Instant\Checkout\Model\Adminhtml\Source;

class LightDarkMode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'light',
                'label' => __('Light mode')
            ],
            [
                'value' => 'dark',
                'label' => __('Dark mode')
            ],
        ];
    }
}
