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

declare(strict_types=1);

namespace Instant\Checkout\Observer;

use Exception;
use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Service\DoRequest;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

/**
 * Class NotifyInstantOnShipment
 */
class NotifyInstantOnShipment implements ObserverInterface
{
    /**
     * @var InstantHelper
     */
    protected $instantHelper;
    /**
     * @var Transaction
     */
    protected $transaction;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ScopeConfig
     */
    protected $scopeConfig;
    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;
    /**
     * @var ShipmentItemRepositoryInterface
     */
    protected $shipItemRepository;

    /**
     * @var DoRequest
     */
    protected $doRequest;

    /**
     * NotifyInstantOnShipment constructor.
     * @param Transaction $transaction
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param ScopeConfig $scopeConfig
     * @param InstantHelper $instantHelper
     * @param DoRequest $doRequest
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ShipmentItemRepositoryInterface $shipItemRepository
     */
    public function __construct(
        Transaction $transaction,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        ScopeConfig $scopeConfig,
        InstantHelper $instantHelper,
        DoRequest $doRequest,
        OrderItemRepositoryInterface $orderItemRepository,
        ShipmentItemRepositoryInterface $shipItemRepository
    ) {
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->instantHelper = $instantHelper;
        $this->doRequest = $doRequest;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipItemRepository = $shipItemRepository;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();

        if ($order->getPayment()->getMethod() === 'instant') {
            try {
                /** @var Shipment $shipment */
                $shipment = $observer->getEvent()->getShipment();
                /** @var Order $order */
                $order = $observer->getEvent()->getShipment()->getOrder();
                $json = $this->getPayloadJson($shipment, $order);
                $this->doRequest->execute('order/fulfil', $json);
            } catch (Exception $e) {
                return;
            }
        }
    }

    /**
     * @param $shipment
     * @param $order
     * @return array
     */
    protected function getPayloadJson(Shipment $shipment, Order $order)
    {
        /** @var ShipmentTrackInterface[] $tracks */
        $tracks = $shipment->getTracks();
        $carrier = 'none';
        $trackingNumber = 'none';
        /** @var ShipmentTrackInterface $track */
        foreach ($tracks as $track) {
            $carrier = $track->getTitle();
            $trackingNumber = $track->getTrackNumber();
        }
        return [
            'platformOrderId' => $order->getId(),
            'trackingNumber' => $trackingNumber,
            'carrier' => $carrier,
            'storeCode' => $order->getStore()->getCode(),
        ];
    }
}
