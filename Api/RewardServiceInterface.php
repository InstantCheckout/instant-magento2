<?php

namespace Instant\Checkout\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Interface RewardServiceInterface
 */
interface RewardServiceInterface
{
    /**
     * Get the reward points balance for a customer.
     *
     * @param CustomerInterface $customer
     * @return int
     */
    public function getRewardPointsBalance(CustomerInterface $customer): int;

    /**
     * Apply reward points to a quote.
     *
     * @param int $points
     * @param CartInterface $quote
     * @param CustomerInterface $customer
     * @return void
     */
    public function applyRewardPointsToQuote(int $points, CartInterface $quote, CustomerInterface $customer);
}