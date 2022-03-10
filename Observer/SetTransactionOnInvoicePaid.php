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

namespace Instant\Checkout\Observer;

use Exception;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class SetTransactionOnInvoicePaid implements ObserverInterface
{
    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var InstantHelper
     */
    protected $instantHelper;

    public function __construct(
        BuilderInterface $transactionBuilder,
        InstantHelper $instantHelper
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->instantHelper = $instantHelper;
    }

    public function createTransaction($order = null, $paymentData = array())
    {
        try {
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setParentTransactionId($paymentData['id']);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );

            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        if ($order->getPayment()->getMethod() === "instant") {
            $txId = $this->createTransaction($order, ['id' => $this->instantHelper->guid()]);
            $invoice->setTransactionId($txId);
        }
    }
}
