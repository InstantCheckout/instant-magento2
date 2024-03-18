<?php


namespace Instant\Checkout\Observer\Sales\Order;

use Exception;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Magento\Sales\Model\Order;

class LinkGuestOrderToCustomer implements ObserverInterface
{

    /**
     * @var CollectionFactory
     */
    protected $factory;
    /**
     * @var InstantHelper
     */
    protected $instantHelper;
    /**
     * @var OrderCustomerManagementInterface
     */
    protected $orderCustomerService;

    /**
     * @deprecated 101.0.4
     */
    protected $addressFactory;

    public function __construct(
        CollectionFactory $factory,
        InstantHelper $instantHelper,
        OrderCustomerManagementInterface $orderCustomerService,
        AddressFactory $addressFactory
    ) {
        $this->factory = $factory;
        $this->instantHelper = $instantHelper;
        $this->orderCustomerService = $orderCustomerService;
        $this->addressFactory = $addressFactory;
    }

    public function assignDefaultBillingAndShippingAddressesFromOrder(Order $order, CustomerInterface $customer)
    {
        $billing = $order->getBillingAddress()->getData();
        $shipping = $order->getShippingAddress()->getData();
        $areBillingAndShippingEqual = array_diff($billing, $shipping);
        $shouldSaveShipping = !$order->getIsVirtual();

        /* Shipping and billing address are the same. Save a single address and set default billing + shipping. */
        if ($areBillingAndShippingEqual) {
            $address = $this->addressFactory->create();
            $address->setData($order->getShippingAddress()->getData());
            $address->setCustomerId($customer->getId())
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping($shouldSaveShipping ? '1' : '0')
                ->setSaveInAddressBook('1');
            $address->save();
            return;
        }

        /* Save billing address */
        $address = $this->addressFactory->create();
        $address->setData($order->getBillingAddress()->getData());
        $address->setCustomerId($customer->getId())
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('0')
            ->setSaveInAddressBook('1');
        $address->save();

        /* Save shipping address if we should save shipping */
        if ($shouldSaveShipping) {
            $address = $this->addressFactory->create();
            $address->setData($order->getShippingAddress()->getData());
            $address->setCustomerId($customer->getId())
                ->setIsDefaultBilling('0')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');
            $address->save();
        }
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethod() != 'instant') {
            return;
        }
        if ($order->getCustomerId()) {
            $this->instantHelper->addCommentToOrder($order, 'Order is a customer order - skipping guest order assignment.');
            return;
        }

        $order->save();

        $email = $order->getCustomerEmail();
        $customer = $this->factory->create()->addFieldToFilter('email', $email);
        if ($customer->count() > 0) {
            $customer = $customer->getFirstItem();
        } else {
            $customer = false;
        }

        $shouldConvertGuestToCustomer = $this->instantHelper->getConfigField($this->instantHelper::AUTO_CONVERT_GUEST_TO_CUSTOMER);
        if ($shouldConvertGuestToCustomer && !$customer) {
            $this->instantHelper->addCommentToOrder($order, 'Customer for this order does not exist. Converting guest to customer.');
            $customer = $this->orderCustomerService->create($order->getId());
            $this->assignDefaultBillingAndShippingAddressesFromOrder($order, $customer);
            $this->instantHelper->addCommentToOrder($order, 'Customer created.');

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            try {
                $subscriptionManager = $objectManager->create('Magento\Newsletter\Model\SubscriptionManager');
                $this->instantHelper->addCommentToOrder($order, 'Subscribing customer to newsletter');
                $subscriptionManager->subscribeCustomer((int) $customer->getId(), (int) $order->getStore()->getId());
            } catch (Exception $e) {
                $this->instantHelper->logInfo("[INSTANT::Instant/Checkout/Observer/Sales/Order/LinkGuestOrderToCustomer] Exception raised.");
                $this->instantHelper->logInfo($e->getMessage());
            }
        }

        if ($customer) {
            $this->instantHelper->addCommentToOrder($order, 'Assigning customer ' . $customer->getId() . ' to order.');
            /** @var Customer $customer */
            $order->setCustomerId($customer->getId());
            $order->setCustomerIsGuest(0);
            $order->setCustomerGroupId($customer->getGroupId());
            $order->save();
            $this->instantHelper->addCommentToOrder($order, 'Customer assigned to order.');

        }
    }
}