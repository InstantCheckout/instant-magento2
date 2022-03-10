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

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPutActionInterface;
use Magento\Checkout\Model\Session;

class Clear extends Action implements HttpPutActionInterface
{
    private $session;

    /**
     * Constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Session $session
    ) {
        $this->session = $session;

        return parent::__construct($context);
    }

    /**
     * Clears the cart of the current session.
     */
    public function execute()
    {
        $this->session->setQuoteId(null);
    }
}
