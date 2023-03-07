<?php

namespace Instant\Checkout\Api;

use Instant\Checkout\Api\ServiceInterface;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;

class Service implements ServiceInterface
{

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var Order
     */
    private $order;

    /**
     * Service constructor.
     *
     * @param \Magento\Checkout\Helper\Data                                $checkoutHelper
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \Magento\Framework\Serialize\SerializerInterface             $serializer
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Quote\Model\QuoteFactory                            $quoteFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface                  $orderRepository
     * @param \Magento\Sales\Model\Order                                   $order 
     */
    public function __construct(

        Data $checkoutHelper,
        Session $checkoutSession,
        SerializerInterface $serializer,
        Registry $registry,
        QuoteFactory $quoteFactory,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        Order $order
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
        $this->registry = $registry;
        $this->quoteFactory = $quoteFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->orderManagement = $orderManagement;
    }

    public function deleteLastRealOrder()
    {
        $lastRealOrderId = $this->checkoutSession->getLastRealOrderId();
        if (empty($lastRealOrderId)) {
            return $this->serializer->serialize([]);
        }

        $order = $this->order->loadByIncrementId($lastRealOrderId);
        $this->orderManagement->cancel($order->getId());
        $this->orderRepository->delete($order);
    }

    /**
     * Handles a failed payment
     *
     * @api
     *
     * @return mixed
     */
    public function handle_failed_payment()
    {
        try {
            $this->restoreQuote();
            $this->deleteLastRealOrder();

            return $this->serializer->serialize([]);
        } catch (\Exception $e) {
            return $this->serializer->serialize([
                "error" => $e->getMessage()
            ]);
        }
    }

    private function restoreQuote()
    {
        $checkout = $this->checkoutHelper->getCheckout();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $checkout->getLastRealOrder();

        if ($order->getId()) {
            try {
                $id = $this->checkoutSession->getLastQuoteId();
                $quote = $this->quoteFactory->create()->loadByIdWithoutStore($id);
                if (!$quote->getId()) {
                    return false;
                }
                $quote->setIsActive(true)->setReservedOrderId(null)->save();
                $this->checkoutSession->replaceQuote($quote);
                return true;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }

        return false;
    }
}
