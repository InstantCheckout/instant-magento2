<?php

namespace Instant\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    public function __construct(
    ) {
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $data = [];


        $data = [
            'payment' => [
                'instant' => []
            ]
        ];

        return $data;
    }
}