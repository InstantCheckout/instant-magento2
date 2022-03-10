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

namespace Instant\Checkout\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;
use Instant\Checkout\Api\Data\RequestLogInterface;

/**
 * Interface RequestLogSearchResultsInterface
 */
interface RequestLogSearchResultsInterface extends SearchResultsInterface
{

    /**
     * Get RequestLog list.
     * @return \Instant\Checkout\Api\Data\RequestLogInterface[]
     */
    public function getItems();

    /**
     * Set request_id list.
     * @param RequestLogInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
