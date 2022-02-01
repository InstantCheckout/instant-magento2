<?php

declare(strict_types=1);

namespace Instant\Checkout\Model\Payment;

use Magento\Framework\App\ObjectManager;
use Instant\Checkout\Model\Config\InstantConfig;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Instant\Checkout\Service\DoRequest;

/**
 * Payment Method for all orders placed through Instant
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InstantPayment extends AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = "instant";
    /**
     * @var bool
     */
    protected $_isOffline = false;
    /**
     * @var
     */
    protected $_custompayments;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canCapturePartial = true;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    protected $instantConfig;

    /**
     * @var DoRequest
     */
    protected $doRequest;

    /**
     * InstantPayment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentHelper $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentLogger $logger
     * @param DoRequest $doRequest
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        PaymentLogger $logger,
        DoRequest $request,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->instantConfig = ObjectManager::getInstance()->get(InstantConfig::class);
        $this->doRequest = $request;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            null
        );
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|AbstractMethod
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        if ($order->getPayment()->getMethod() === "instant") {
            try {
                $this->sendRefund($order, $payment, $amount);
            } catch (\Exception $e) {
                throw new CouldNotSaveException(__('Payment refunding error.'));
            }

            $payment
                ->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(1);
        }

        return $this;
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function isAvailable(CartInterface $quote = null)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * @param $order
     * @param $payment
     * @param $amount
     * @return string
     * @throws LocalizedException
     */
    protected function sendRefund($order, $payment, $amount)
    {
        if ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for refund.'));
        }

        if ($amount > $order->getBaseGrandTotal()) {
            throw new LocalizedException(__('Invalid amount for refund.'));
        }

        $payload = [
            'platformOrderId' => $order->getId(),
            'amountToRefund' => (string)number_format($payment->getCreditMemo()->getBaseGrandTotal(), 2, '.', ''),
            'taxAmount' => (string)number_format($payment->getCreditMemo()->getTaxAmount(), 2, '.', ''),
            'shippingAmount' => (string)number_format($payment->getCreditMemo()->getShippingInclTax(), 2, '.', ''),
            'storeCode' => $order->getStore()->getCode(),
        ];

        $this->doRequest->execute('/order/refund', $payload);
    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $this->setPaymentFormUrl($payment);
        $stateObject->setIsNotified(false);
    }
}
