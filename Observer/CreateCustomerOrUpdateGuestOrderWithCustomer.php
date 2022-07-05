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
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;

/**
 * Class CreateCustomerOrUpdateGuestOrderWithCustomer
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
     * @var Order
     */
    protected $order;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * CreateCustomerOrUpdateGuestOrderWithCustomer constructor.
     * @param PurchasedFactory $purchasedFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param Order $order
     * @param LoggerInterface $logger
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger     
     */
    public function __construct(
        PurchasedFactory $purchasedFactory,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        Order $order,
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        OrderCustomerManagementInterface $orderCustomerService,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository
    ) {
        $this->purchasedFactory = $purchasedFactory;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->order = $order;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->orderCustomerService = $orderCustomerService;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    public function addCommentToOrder(Order $order, string $comment)
    {
        $commentToAdd = '[INSTANT] (Order ID: ' . $order->getId() . '): ' . sprintf($comment);
        $statusComment = NULL;

        try {
            $statusComment = $order->addCommentToStatusHistory($commentToAdd);
            $this->orderStatusRepository->save($statusComment);
        } catch (Exception $e) {
            $order->addStatusHistoryComment($commentToAdd);
            $order->save();
        }
    }

    public function logInfo(Order $order, string $log)
    {
        $this->logger->info('[INSTANT] (Order ID: ' . $order->getId() .  '): ' . $log);
    }

    public function logError(Order $order, string $log)
    {
        $this->logger->error('[INSTANT] (Order ID: ' . $order->getId() .  '): ' . $log);
    }

    /**
     * @param EventObserver $observer
     * @throws Exception
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getInvoice()->getOrder();
        $this->logInfo($order, "In CreateCustomerOrUpdateGuestOrderWithCustomer");

        $incrementId = $order->getIncrementId();
        $orderId = $order->getEntityId();
        $orderPaymentMethod = $order->getPayment()->getMethod();

        // If this is an Instant order, then proceed
        if ($orderPaymentMethod == "instant") {
            $this->logInfo($order, "Instant order detected.");
            try {
                $customer = NULL;
                $shouldCreateCustomerIfNotExists = false;
                $shouldSubscribeCustomerToNewsletter = false;

                // Check whether we should create an account and/or subscribe existing or new account to newsletter
                try {
                    foreach ($order->getStatusHistoryCollection() as $status) {
                        if ($status->getComment()) {
                            if (strpos($status->getComment(), 'SUBSCRIBE_TO_NEWSLETTER') !== false) {
                                $shouldSubscribeCustomerToNewsletter =  true;
                                $this->logInfo($order, "Detected that we should subscribe new/existing customer to newsletter.");
                            }

                            if (strpos($status->getComment(), 'CREATE_WEBSITE_USER') !== false) {
                                $shouldCreateCustomerIfNotExists = true;
                                $this->logInfo($order, "Detected that we should create customer if not exists.");
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->logError($order, "Error occurred when scanning order comments. " . $e->getMessage());
                }


                if ($order->getCustomerIsGuest()) {
                    $this->logInfo($order, "Attempting to get customer with email: " . $order->getCustomerEmail());
                    try {
                        $customer = $this->customerRepository->get($order->getCustomerEmail());
                    } catch (Exception $e) {
                        // do nothing. Customer with order email does not exist.
                    }

                    if ($customer) {
                        $this->logInfo($order, "Order is guest order and customer exists.");
                        $this->addCommentToOrder($order, sprintf('Customer with email ' . $order->getCustomerEmail() . ' exists. Assigning order to this customer.'));
                    } else if ($shouldCreateCustomerIfNotExists) {
                        $this->logInfo($order, "Customer with email: " . $order->getCustomerEmail() . " does not exist.");
                        $this->addCommentToOrder($order, sprintf('Creating account for customer with email: ' . $order->getCustomerEmail()));
                        $customer = $this->orderCustomerService->create($orderId);
                        $this->logInfo($order, "New customer account created.");
                    }

                    if ($customer) {
                        $this->logInfo($order, "Updating order. Converting guest order to customer order.");
                        $customerOrder = $this->orderRepository->get($orderId);
                        $customerOrder->setCustomerIsGuest(0);
                        $customerOrder->setCustomerId($customer->getId());
                        $customerOrder->setCustomerGroupId($customer->getGroupId());

                        $this->logInfo($order, "Saving order.");
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
                } else {
                    $customer = $order->getCustomer();
                    $this->addCommentToOrder($order, sprintf('Customer order detected. Skipping guest order customer assignment.'));
                    $this->logInfo($order, "Customer order detected. Skipping guest order customer assignment.");
                }

                if ($customer && $shouldSubscribeCustomerToNewsletter) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    try {
                        $subscriptionManager = $objectManager->create('Magento\Newsletter\Model\SubscriptionManager');
                        $this->logInfo($order, "Subscribing customer to newsletter");
                        $this->addCommentToOrder($order, sprintf('Subscribing customer to newsletter.'));
                        $subscriptionManager->subscribeCustomer((int)$customer->getId(), (int)$order->getStore()->getId());
                    } catch (Exception $e) {
                        $this->logInfo($order, "Unable to subscribe customer to newsletter as this Magento instance does not have Magento\Newsletter\Model\SubscriptionManager.");
                        // do nothing. Subscription manager does not exist in this magento version.
                    }
                }
            } catch (Exception $e) {
                $this->logError($order, "Exception raised in Instant/Checkout/Observer/UpdateGuestOrderWithCustomer");
                $this->logError($order, $e->getMessage());
            }
        }
    }
}
