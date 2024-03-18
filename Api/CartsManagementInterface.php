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
     * Get masked id for cart (quote) id
     * @param string $cartId
     * @return string
     */
    public function getMaskedIdForCartId($cartId);
}
