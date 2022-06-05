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

declare(strict_types=1);

namespace Instant\Checkout\Math;

/**
 * Contains methods to compare float digits.
 *
 * @api
 * @since 101.0.6
 */
class FloatComparator
{
    /**
     * Precision for floats comparing.
     *
     * @var float
     */
    private static $epsilon = 0.00001;

    /**
     * Compares two float digits.
     *
     * @param float $a
     * @param float $b
     * @return bool
     * @since 101.0.6
     */
    public function equal(float $a, float $b): bool
    {
        return abs($a - $b) <= self::$epsilon;
    }

    /**
     * Compares if the first argument greater than the second argument.
     *
     * @param float $a
     * @param float $b
     * @return bool
     * @since 101.0.6
     */
    public function greaterThan(float $a, float $b): bool
    {
        return ($a - $b) > self::$epsilon;
    }

    /**
     * Compares if the first argument greater or equal to the second.
     *
     * @param float $a
     * @param float $b
     * @return bool
     * @since 101.0.6
     */
    public function greaterThanOrEqual(float $a, float $b): bool
    {
        return $this->equal($a, $b) || $this->greaterThan($a, $b);
    }
}
