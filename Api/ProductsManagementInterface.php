<?php

namespace Instant\Checkout\Api;

/**
 * Interface for Instant product management
 * @api
 * @since 100.0.2
 */
interface ProductsManagementInterface
{
    /**
     * Gets special price if active, else returns product price
     *
     * @param string $storeCode
     * @param string $sku
     * @return string
     */
    public function getPrice(
        $storeCode,
        $sku
    );
}
