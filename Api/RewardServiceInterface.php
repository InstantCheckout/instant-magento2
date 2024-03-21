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
     * @param int $customerId
     * @return int
     */
    public function getRewardPointsBalance(int $customerId): int;

    /**
     * Apply reward points to a quote.
     *
     * @param CartInterface $quote
     * @param int $customerId
     * @return void
     */
    public function applyRewardPointsToQuote(CartInterface $quote, int $customerId);
}