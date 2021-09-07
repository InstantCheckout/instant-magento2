<?php

namespace Instant\Checkout\Model\Payment;

class Instant extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'instant';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
