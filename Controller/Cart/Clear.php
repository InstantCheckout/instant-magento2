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

namespace Instant\Checkout\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class Clear extends Action
{
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        ResultJsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;

        return parent::__construct($context);
    }

    /**
     * Clears the cart of the current session.
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteId = $quote->getId();

        if ($quoteId) {
            $cart = $this->quoteRepository->get($quoteId);
            $cart->delete();
        }
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->clearStorage();
        $this->checkoutSession->restoreQuote();
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true]);
    }
}
