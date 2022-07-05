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

namespace Instant\Checkout\Model;

use Exception;
use Instant\Checkout\Api\CartsManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;
use Instant\Checkout\Math\FloatComparator;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;

/**
 * Class for management of carts information.
 */
class CartsManagement implements CartsManagementInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;


    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ProductFactory $productFactory,
        StoreRepositoryInterface $storeRepository,
        LoggerInterface $logger,
        CartRepositoryInterface $cartRepository,
        ResultJsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
    ) {
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
    }

    /**
     * Retrieves simple products of quote items
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    public function getAllVisibleItems($quote)
    {
        $items = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getTypeId() != 'configurable') {
                $items[] = $item;
            }
        }

        return $items;
    }

    /*
    * @param string $cartId
    * @return string
    */
    public function getMaskedIdForCartId($cartId)
    {
        $maskedId = null;
        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($cartId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(__("The quote wasn't found. Verify the quote ID and try again."));
        }

        return $maskedId;
    }

    /*
    * @return string
    */
    public function amendCustomerIdNullForGuestCarts()
    {
        $connection = $this->resourceConnection->getConnection();
        $query = "UPDATE quote SET customer_id = NULL WHERE customer_is_guest = true";
        $connection->query($query);
    }


    /*
    * @param string $cartId
    * @param bool $active
    * @return string
    */
    public function setActive($cartId, $active)
    {
        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($cartId);
        if (!$quote->getId()) {
            throw new LocalizedException(
                __("The quote wasn't found. Verify the quote ID and try again.")
            );
        }

        $quote->setIsActive((bool)$active)->save();
        return true;
    }

    /*
    * @param string $storeCode
    * @param string $fromCartId
    * @param string $targetCartId
    * @return string
    */
    public function merge(
        $storeCode,
        $fromCartId,
        $targetCartId
    ) {
        try {
            $fromQuote = NULL;
            $finalQuote = NULL;

            if (strlen($fromCartId) === 32) {
                $fromCartId = $this->quoteIdMaskFactory->create()->load($fromCartId, 'masked_id')->getQuoteId();
                $fromQuote = $this->quoteFactory->create()->loadByIdWithoutStore($fromCartId);
            } else {
                $fromQuote = $this->quoteFactory->create()->loadByIdWithoutStore($fromCartId);
            }

            if (strlen($targetCartId) === 32) {
                $toCartId = $this->quoteIdMaskFactory->create()->load($targetCartId, 'masked_id')->getQuoteId();
                $finalQuote = $this->quoteFactory->create()->loadByIdWithoutStore($toCartId);
            } else {
                $finalQuote = $this->cartRepository->get($targetCartId);
            }

            $fromQuoteItems = $this->getAllVisibleItems($fromQuote);
            $finalQuoteItems = $this->getAllVisibleItems($finalQuote);

            foreach ($fromQuoteItems as $item) {
                $found = false;

                foreach ($finalQuoteItems as $quoteItem) {
                    $found = false;
                    if ($item->getProduct()->getSku() === $quoteItem->getProduct()->getSku()) {
                        $fromQuoteItemPrice = $item->getParentItemId() ? floatval($item->getParentItem()->getPrice()) : floatval($item->getPrice());
                        $quoteItemPrice = $quoteItem->getParentItemId() ? floatval($quoteItem->getParentItem()->getPrice()) : floatval($quoteItem->getPrice());

                        $comparator = new FloatComparator();

                        if ($comparator->equal($quoteItemPrice, $fromQuoteItemPrice)) {
                            $found = true;

                            $quoteItem = $quoteItem->getParentItemId() ? $quoteItem->getParentItem() : $quoteItem;
                            $item = $item->getParentItemId() ? $item->getParentItem() : $item;

                            $quoteItem->setQty($quoteItem->getQty() + $item->getQty());
                            $quoteItem->save();
                            break;
                        }
                    }
                }

                if (!$found) {
                    $newItem = clone $item;

                    if ($item->getParentItemId()) {
                        $newItem = clone $item->getParentItem();
                    }

                    $finalQuote->addItem($newItem);
                    if ($item->getHasChildren()) {
                        foreach ($item->getChildren() as $child) {
                            $newChild = clone $child;
                            $newChild->setParentItem($newItem);
                            $finalQuote->addItem($newChild);
                        }
                    }
                    $newItem->save();
                }
            }

            if (!$finalQuote->getId()) {
                $finalQuote->getShippingAddress();
                $finalQuote->getBillingAddress();
            }

            $fromQuote->setIsActive(false);
            $finalQuote->save();
        } catch (Exception $e) {
            $this->logger->error("Exception raised in Instant/Checkout/Model/CartsManagement");
            $this->logger->error($e->getMessage());
        }
    }
}
