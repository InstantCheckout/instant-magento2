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
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;

/**
 * Class UpdateGuestOrderWithCustomer
 */
class CreateCustomerOrUpdateGuestOrderWithCustomer implements ObserverInterface
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
     * @var SubscriptionManagerInterface
     */
    private SubscriptionManagerInterface $subscriptionManager;

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
        AccountManagementInterface $customerAccountManagement,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        Order $order,
        OrderSender $orderSender,
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        AddressFactory $addressFactory,
        OrderCustomerManagementInterface $orderCustomerService,
        SubscriptionManagerInterface $subscriptionManager
    ) {
        $this->purchasedFactory = $purchasedFactory;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->addressFactory = $addressFactory;
        $this->orderCustomerService = $orderCustomerService;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function addCommentToOrder(Order $order, string $comment)
    {
        $orderComment = sprintf($comment);
        $comment = $order->addCommentToStatusHistory($orderComment);
        $this->orderStatusRepository->save($comment);
    }

    /**
     * @param EventObserver $observer
     * @throws Exception
     */
    public function execute(EventObserver $observer)
    {
        $this->logger->error("in CreateCustomerOrUpdateGuestOrderWithCustomer");
        /** @var Order $order */
        $order = $observer->getEvent()->getInvoice()->getOrder();

        $incrementId = $order->getIncrementId();
        $orderId = $order->getEntityId();
        $orderPaymentMethod = $order->getPayment()->getMethod();

        // If this is an Instant order, then proceed
        if ($orderPaymentMethod == "instant") {
            $this->logger->info("Instant order detected.");
            try {
                $customer = NULL;
                $shouldCreateCustomerIfNotExists = false;
                $shouldSubscribeCustomerToNewsletter = false;

                // Check whether we should create an account and/or subscribe existing or new account to newsletter
                try {
                    foreach ($order->getStatusHistoryCollection() as $status) {
                        if ($status->getComment()) {
                            if (str_contains($status->getComment(), 'SUBSCRIBE_TO_NEWSLETTER')) {
                                $shouldSubscribeCustomerToNewsletter =  true;
                                $this->logger->info("Detected that we should subscribe new/existing customer to newsletter.");
                            }

                            if (str_contains($status->getComment(), 'CREATE_CUSTOMER')) {
                                $shouldCreateCustomerIfNotExists = true;
                                $this->logger->info("Detected that we should create customer if not exists.");
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->error("Error occurred when scanning order comments.");
                }

                $this->logger->info("Attempting to get customer with email: " . $order->getCustomerEmail());
                try {
                    $customer = $this->customerRepository->get($order->getCustomerEmail());
                } catch (Exception $e) {
                    // do nothing. Customer with order email does not exist.
                }

                if ($order->getCustomerIsGuest() && $customer) {
                    $this->logger->info("Customer with email: " . $order->getCustomerEmail() . " exists.");
                    $this->addCommentToOrder($order, sprintf('Instant: Customer with email ' . $order->getCustomerEmail() . ' exists. Assigning order to this customer.'));
                } else if ($shouldCreateCustomerIfNotExists) {
                    $this->logger->info("Customer with email: " . $order->getCustomerEmail() . " does not exist.");
                    $this->addCommentToOrder($order, sprintf('Instant: Creating account for customer with email: ' . $order->getCustomerEmail()));
                    $customer = $this->orderCustomerService->create($orderId);
                    $this->logger->info("New customer account created.");
                }

                if ($customer) {
                    if ($shouldSubscribeCustomerToNewsletter) {
                        $this->addCommentToOrder($order, sprintf('Instant: Subscribing customer to newsletter.'));
                        $this->subscriptionManager->subscribeCustomer((int)$customer->getId(), (int)$order->getStore()->getId());
                    }
                    $this->logger->info("Updating order. Converting guest order to customer order.");
                    $customerOrder = $this->orderRepository->get($orderId);
                    $customerOrder->setCustomerIsGuest(0);
                    $customerOrder->setCustomerId($customer->getId());
                    $customerOrder->setCustomerGroupId($customer->getGroupId());
                    $this->orderSender->send($customerOrder, true);

                    $this->logger->info("Saving order.");
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
            } catch (Exception $e) {
                $this->logger->error("Exception raised in Instant/Checkout/Observer/UpdateGuestOrderWithCustomer");
                $this->logger->error($e->getMessage());
            }
        }
    }
}
