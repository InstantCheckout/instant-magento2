<?php

namespace Instant\Checkout\Service;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Instant\Checkout\Api\RewardServiceInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * RewardService constructor.
     *
     * @param RewardFactory $rewardFactory
     * @param LoggerInterface $logger
	 * @param ScopeConfigInterface $scopeConfig
	 * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        RewardFactory $rewardFactory,
        LoggerInterface $logger,
		ScopeConfigInterface $scopeConfig,
		CustomerRepositoryInterface $customerRepository
    ) {
        $this->rewardFactory = $rewardFactory;
        $this->logger = $logger;
		$this->scopeConfig = $scopeConfig;
		$this->customerRepository = $customerRepository;
    }

    /**
     * Get the reward points balance for a customer.
     *
     * @param int $customerId
     * @return int
     */
    public function getRewardPointsBalance(int $customerId)
    {
        try {
            // Retrieve the customer object by customerId
            $customer = $this->customerRepository->getById($customerId);
            
            /** @var \Magento\Reward\Model\RewardFactory $reward */
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
     * @param CartInterface $quote
     * @param int $customerId
     * @return bool
     */
	public function applyRewardPointsToQuote(CartInterface $quote, int $customerId)
	{
		try {
            $customer = $this->customerRepository->getById($customerId);
            $availablePoints = $this->getRewardPointsBalance($customer->getId());
        
            // Log the retrieved balance for debugging.
            $this->logInfo('Retrieved ' . $availablePoints . ' reward points for customer ' . $customer->getCustomerEmail());
    
            // Define or retrieve a conversion rule
            // TO-DO: Verify this path (reward/general/points_conversion_rate) is correct once we have access to the dev environment.
            $conversionRate = $this->scopeConfig->getValue(
                'reward/general/points_conversion_rate',
                ScopeInterface::SCOPE_STORE
            );
            
            // Convert quote total to points required
            $pointsRequired = (int)round($quote->getGrandTotal() * $conversionRate);
    
            // Check if the customer has enough reward points
            if ($pointsRequired > $availablePoints) {
                throw new LocalizedException(__('Insufficient reward points.'));
            }
            
            // Instead of taking points as a parameter, determine how many points to apply based on the quote
            $pointsToApply = min($pointsRequired, $availablePoints);
    
            // Convert reward points to currency amount
            $rewardAmount = $this->convertPointsToAmount($pointsToApply);
    
            // Apply the reward amount to the quote
            $quote->setUseRewardPoints(true);
            $quote->setRewardPointsBalance($pointsToApply); // The actual points applied
            $quote->setRewardCurrencyAmount($rewardAmount);
            $quote->setBaseRewardCurrencyAmount($rewardAmount);
            $quote->collectTotals();
    
            // Save the quote to persist changes
            $quote->save();
	
			$this->logInfo('Successfully applied ' . $pointsToApply . ' reward points to quote ' . $quote->getId());
            
            return true;
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
            'reward/general/points_conversion_rate',
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