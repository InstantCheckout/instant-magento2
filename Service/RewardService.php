<?php

namespace Instant\Checkout\Service;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Instant\Checkout\Api\RewardServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Reward\Model\RewardFactory;
use Psr\Log\LoggerInterface;

class RewardService implements RewardServiceInterface
{
    /**
     * @var RewardFactory
     */
    protected $rewardFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;
	
	/**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * RewardService constructor.
     *
     * @param RewardFactory $rewardFactory
     * @param LoggerInterface $logger
	 * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        RewardFactory $rewardFactory,
        LoggerInterface $logger,
		ScopeConfigInterface $scopeConfig
    ) {
        $this->rewardFactory = $rewardFactory;
        $this->logger = $logger;
		$this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the reward points balance for a customer.
     *
     * @param CustomerInterface $customer
     * @return int
     */
    public function getRewardPointsBalance(CustomerInterface $customer)
    {
        try {
            /** @var Reward $reward */
            $reward = $this->rewardFactory->create();
            $reward->setCustomer($customer);
            $reward->setWebsiteId($customer->getWebsiteId());
            $reward->loadByCustomer();

            $pointsBalance = $reward->getPointsBalance();
            $this->logInfo('Reward points balance for customer ' . $customer->getId() . ': ' . $pointsBalance);

            return $pointsBalance;
        } catch (Exception $e) {
            $this->logError('Error retrieving reward points balance: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Apply reward points to a quote.
     *
     * @param int $points
     * @param CartInterface $quote
     * @param CustomerInterface $customer
     * @return void
     */
	public function applyRewardPointsToQuote($points, CartInterface $quote, CustomerInterface $customer)
	{
		try {
			$this->logInfo('Applying ' . $points . ' reward points to quote ' . $quote->getId() . ' for customer ' . $customer->getId());
	
			// Check if the customer has enough reward points
			$availablePoints = $this->getRewardPointsBalance($customer);
			if ($points > $availablePoints) {
				throw new LocalizedException(__('Insufficient reward points.'));
			}
	
			// Convert reward points to currency amount
			$rewardAmount = $this->convertPointsToAmount($points);
	
			// Apply the reward amount to the quote
			$quote->setUseRewardPoints(true);
			$quote->setRewardPointsBalance($availablePoints);
			$quote->setRewardCurrencyAmount($rewardAmount);
			$quote->setBaseRewardCurrencyAmount($rewardAmount);
			$quote->collectTotals();
	
			// Save the quote
			$quote->save();
	
			$this->logInfo('Successfully applied ' . $points . ' reward points to quote ' . $quote->getId());
		} catch (Exception $e) {
			$this->logError('Error applying reward points to quote: ' . $e->getMessage());
			throw new LocalizedException(__('Could not apply reward points to the quote.'));
		}
	}
	
	protected function convertPointsToAmount($points)
    {
        // Retrieve the points conversion rate from the Magento configuration
        // TO-DO: Verify this path (reward/general/points_money) is correct once we have access to the dev environment. 
        $pointsValue = $this->scopeConfig->getValue(
            'reward/general/points_money',
            ScopeInterface::SCOPE_STORE
        );

        // Convert reward points to currency amount
        $amount = $points / $pointsValue;
        return $amount;
    }
	
    /**
     * Log an info message.
     *
     * @param string $message
     */
    protected function logInfo($message)
    {
        $this->logger->info('[INSTANT]: ' . $message);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     */
    protected function logError($message)
    {
        $this->logger->error('[INSTANT]: ' . $message);
    }
}