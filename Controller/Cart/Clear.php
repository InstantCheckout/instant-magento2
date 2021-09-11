<?php

namespace Instant\Checkout\Controller\Cart;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPutActionInterface;

class Clear extends Action implements HttpPutActionInterface
{
    /**
     * Constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        return parent::__construct($context);
    }

    /**
     * Clears the cart of the current session.
    */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cartObject = $objectManager->create('Magento\Checkout\Model\Cart')->truncate();
        $cartObject->saveQuote();
    }
}
