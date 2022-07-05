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

namespace Instant\Checkout\Api;

/**
 * Interface for Instant cart management
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

    /**
     * Set cart active status
     *
     * @param string $cartId
     * @param bool $active
     * @return string
     */
    public function setActive(
        $cartId,
        $active
    );

    /**
     * Amend carts to ensure all guest carts do not have customer id associated
     *
     * @return string
     */
    public function amendCustomerIdNullForGuestCarts();
}
