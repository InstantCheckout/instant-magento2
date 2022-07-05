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
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;

class Clear extends Action
{
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        ResultJsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;

        return parent::__construct($context);
    }

    /**
     * Clears the cart of the current session.
     */
    public function execute()
    {
        $this->checkoutSession->getQuote()->setIsActive(false)->save();

        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true]);
    }
}
