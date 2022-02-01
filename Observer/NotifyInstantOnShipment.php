<?php

declare(strict_types=1);

namespace Instant\Checkout\Observer;

use Exception;
use Instant\Checkout\Model\Config\InstantConfig;
use Instant\Checkout\Service\DoRequest;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
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
     * @var InstantConfig
     */
    protected $instantConfig;
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
     * @param InstantConfig $fastConfig
     * @param DoRequest $doRequest
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ShipmentItemRepositoryInterface $shipItemRepository
     */
    public function __construct(
        Transaction $transaction,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        ScopeConfig $scopeConfig,
        InstantConfig $instantConfig,
        DoRequest $doRequest,
        OrderItemRepositoryInterface $orderItemRepository,
        ShipmentItemRepositoryInterface $shipItemRepository
    ) {
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->instantConfig = $instantConfig;
        $this->doRequest = $doRequest;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipItemRepository = $shipItemRepository;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Shipment $shipment */
            $shipment = $observer->getEvent()->getShipment();
            /** @var ShipmentItemInterface $item */
            foreach ($shipment->getItemsCollection() as $item) {
                $this->shipItemRepository->save($item);
                /** @var Order $order */
                $order = $observer->getEvent()->getShipment()->getOrder();
                $json = $this->getPayloadJson($shipment, $order);
                $this->doRequest->execute('/order/fulfil', $json);
            }
        } catch (Exception $e) {
            return;
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
