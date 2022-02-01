<?php

namespace Instant\Checkout\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Instant\Checkout\Model\Config\InstantConfig;

class SetTransactionOnInvoicePaid implements ObserverInterface
{
    protected $transactionBuilder;
    protected $instantConfig;

    public function __construct(
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->instantConfig = ObjectManager::getInstance()->get(InstantConfig::class);
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

            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        if ($order->getPayment()->getMethod() === "instant") {
            $txId = $this->createTransaction($order, ['id' => $this->instantConfig->guid()]);
            $invoice->setTransactionId($txId);
        }
    }
}
