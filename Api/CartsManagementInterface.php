<?php

namespace Instant\Checkout\Api;

/**
 * Interface for Instant cart management
 * @api
 * @since 100.0.2
 */
interface CartsManagementInterface
{
    /**
     * Merge two carts
     *
     * @param string $storeCode
     * @param string $fromCartId
     * @param string $targetCartId
     * @return string
     */
    public function merge(
        $storeCode,
        $fromCartId,
        $targetCartId
    );
}
