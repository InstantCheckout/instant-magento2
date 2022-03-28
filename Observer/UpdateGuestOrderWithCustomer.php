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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Downloadable\Model\Link\PurchasedFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateGuestOrderWithCustomer
 */
class UpdateGuestOrderWithCustomer implements ObserverInterface
{
    /**
     * @var PurchasedFactory
     */
    protected $purchasedFactory;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateGuestOrderWithCustomer constructor.
     * @param PurchasedFactory $purchasedFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param Order $order
     * @param OrderSender $orderSender
     * @param Logger $logger
     */
    public function __construct(
        PurchasedFactory $purchasedFactory,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        Order $order,
        OrderSender $orderSender,
        LoggerInterface $logger
    ) {
        $this->purchasedFactory = $purchasedFactory;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     * @throws Exception
     */
    public function execute(EventObserver $observer)
    {
        $this->logger->error("in UpdateGuestOrderWithCustomer");

        // Get order
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $incrementId = $order->getIncrementId();
        $orderId = $order->getEntityId();

        // Get payment method of order
        $orderPaymentMethod = $order->getPayment()->getMethod();
        $this->logger->error("orderPaymentMethod: " . $orderPaymentMethod);

        // If this is an Instant order, then proceed
        if ($orderPaymentMethod == "instant") {
            $this->logger->error("attempting to convert guest order to customer order");
            try {
                // Get customer
                $customer = $this->customerRepository->get($order->getCustomerEmail());
                $customerId = $customer->getId();
                $customerOrder = $order;

                // If customer exists, then proceed
                if ($order->getCustomerIsGuest() && $customerId) {
                    // Log that we are converting this order to a customer order
                    $orderComment = sprintf('Instant: Customer with email ' . $order->getCustomerEmail() . ' exists. Assigning order to this customer.');
                    $this->logger->info($orderComment);
                    $comment = $order->addCommentToStatusHistory($orderComment);
                    $this->orderStatusRepository->save($comment);

                    // Update order fields
                    $customerOrder = $this->orderRepository->get($orderId);
                    $customerOrder->setCustomerIsGuest(0);
                    $customerOrder->setCustomerId($customerId);
                    $customerOrder->setCustomerGroupId($customer->getGroupId());

                    // Save order
                    $this->orderRepository->save($customerOrder);
                    $purchased = $this->purchasedFactory->create()->load(
                        $incrementId,
                        'order_increment_id'
                    );
                    if ($purchased->getId()) {
                        $purchased->setCustomerId($customer->getId());
                        $purchased->save();
                    }
                }

                $this->orderSender->send($customerOrder, true);
            } catch (Exception $e) {
                $this->logger->error("Exception raised in Instant/Checkout/Observer/UpdateGuestOrderWithCustomer");
                $this->logger->error($e->getMessage());
            }
        }
    }
}
