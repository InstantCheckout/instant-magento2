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

use Instant\Checkout\Api\CartsManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Instant\Checkout\Math\FloatComparator;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;

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
     * @codeCoverageIgnore
     */
    public function __construct(
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ProductFactory $productFactory,
        StoreRepositoryInterface $storeRepository,
        LoggerInterface $logger
    ) {
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
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
            $fromCartId = $this->quoteIdMaskFactory->create()->load($fromCartId, 'masked_id')->getQuoteId();
            $toCartId = $this->quoteIdMaskFactory->create()->load($targetCartId, 'masked_id')->getQuoteId();
    
            $fromQuote = $this->quoteFactory->create()->loadByIdWithoutStore($fromCartId);
            $finalQuote = $this->quoteFactory->create()->loadByIdWithoutStore($toCartId);
    
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
        } catch (Exception $e){
            $this->logger->error("Exception raised in Instant/Checkout/Model/CartsManagement");
            $this->logger->error($e->getMessage());
        }
    }
}
