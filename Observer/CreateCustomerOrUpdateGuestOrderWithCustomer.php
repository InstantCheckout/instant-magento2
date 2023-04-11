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
use Magento\Customer\Model\AccountManagement;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Api\Data\CustomerInterface;

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
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var OrderCustomerManagementInterface
     */
    protected $orderCustomerService;

    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

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
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        InstantHelper $instantHelper,
        AddressRepositoryInterface $addressRepository,
        AddressFactory $addressFactory
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
        $this->instantHelper = $instantHelper;
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
    }

    public function addCommentToOrder(Order $order, string $comment)
    {
        $commentToAdd = '[INSTANT] (Order ID: ' . $order->getId() . '): ' . sprintf($comment);
        $statusComment = NULL;

        try {
            $statusComment = $order->addCommentToStatusHistory($commentToAdd);
            $this->orderStatusRepository->save($statusComment);
            $this->logInfo($order, $comment);
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

    public function assignDefaultBillingAndShippingAddressesFromOrder(Order $order, CustomerInterface $customer)
    {
        /* Save billing Address */
        $address = $this->addressFactory->create();

        $address->setData($order->getBillingAddress()->getData());

        $address->setCustomerId($customer->getId())
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('0')
            ->setSaveInAddressBook('1');
        $address->save();

        /* Save shipping Address */
        if (!$order->getIsVirtual()) {
            $address = $this->addressFactory->create();
            $address->setData($order->getShippingAddress()->getData());

            $address->setCustomerId($customer->getId())
                ->setIsDefaultBilling('0')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');
            $address->save();
        }
    }

    public function tryGetCustomer(Order $order)
    {
        try {
            $customer = $this->customerRepository->get($order->getCustomerEmail());
            return $customer;
        } catch (Exception $e) {
            // do nothing. Customer with order email does not exist.
            return null;
        }
    }

    public function getShouldCreateCustomer(Order $order)
    {
        $createCustomerParam = $this->instantHelper->getInstantOrderParam($order, 'CREATE_CUSTOMER');
        if ($createCustomerParam == 1) {
            $this->addCommentToOrder($order, "Detected that we should create customer if not exists.");
            return true;
        }

        return false;
    }

    public function getShouldSubscribeToNewsletter(Order $order)
    {
        $subscribeToNewsletterParam = $this->instantHelper->getInstantOrderParam($order, 'SUBSCRIBE_TO_NEWSLETTER');
        if ($subscribeToNewsletterParam == 1) {
            $this->addCommentToOrder($order, "Detected that we should subscribe new/existing customer to newsletter.");
            return true;
        }
        return false;
    }

    public function subscribeCustomerToNewsletter(Order $order, CustomerInterface $customer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $subscriptionManager = $objectManager->create('Magento\Newsletter\Model\SubscriptionManager');
            $this->addCommentToOrder($order, sprintf('Subscribing customer to newsletter.'));
            $subscriptionManager->subscribeCustomer((int)$customer->getId(), (int)$order->getStore()->getId());
        } catch (Exception $e) {
            // do nothing. Subscription manager does not exist in this magento version.
        }
    }

    /**
     * @param EventObserver $observer
     * @throws Exception
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getInvoice()->getOrder();

        $incrementId = $order->getIncrementId();
        $orderId = $order->getEntityId();
        $orderPaymentMethod = $order->getPayment()->getMethod();

        // If this is not an Instant order, then proceed
        if ($orderPaymentMethod != 'instant') {
            return;
        }

        try {
            $customer = NULL;

            $orderIsGuestOrder = $order->getCustomerIsGuest();
            $this->addCommentToOrder($order, "Order is guest: " . ($orderIsGuestOrder == true ? "yes" : "no"));

            if ($orderIsGuestOrder) {
                $customer = $this->tryGetCustomer($order);

                if ($customer) {
                    $this->addCommentToOrder($order, sprintf('Existing customer with email ' . $order->getCustomerEmail() . ' exists. Assigning order to this customer.'));
                } else if ($this->getShouldCreateCustomer($order)) {
                    $this->addCommentToOrder($order, sprintf('A customer with email does not exist. Creating account for customer with email: ' . $order->getCustomerEmail()));
                    $customer = $this->orderCustomerService->create($orderId);
                    // $this->customerAccountManagement->initiatePasswordReset($customer->getEmail(), AccountManagement::EMAIL_RESET);
                    $this->assignDefaultBillingAndShippingAddressesFromOrder($order, $customer);
                }

                if ($customer) {
                    $this->addCommentToOrder($order, "Updating order. Converting guest order to customer order.");
                    $customerOrder = $this->orderRepository->get($orderId);
                    $customerOrder->setCustomerIsGuest(0);
                    $customerOrder->setCustomerId($customer->getId());
                    $customerOrder->setCustomerGroupId($customer->getGroupId());

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
            }

            if ($customer && $this->getShouldSubscribeToNewsletter($order)) {
                $this->subscribeCustomerToNewsletter($order, $customer);
            }
        } catch (Exception $e) {
            $this->addCommentToOrder($order, "Exception raised in Instant/Checkout/Observer/UpdateGuestOrderWithCustomer" . $e->getMessage());
        }
    }
}
