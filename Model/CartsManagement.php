<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Instant\Checkout\Model;

use Instant\Checkout\Api\CartsManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Framework\Math\FloatComparator;
use \Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class for management of carts information.
 */
class CartsManagement implements CartsManagementInterface
{
    protected $productRepository;
    protected $jsonResultFactory;
    protected $quoteFactory;
    protected $maskedQuoteIdToQuoteId;
    protected $productFactory;
    protected $storeRepository;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        ProductFactory $productFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->quoteFactory = $quoteFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->productFactory = $productFactory;
    }

    /**
     * Retrieves simple products of quote items (yes, this is different than Magento's method)
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    public function getAllVisibleItems($quote)
    {
        $items = [];
        foreach ($quote->getItemsCollection() as $item) {
            if ($item->getProductType() === 'simple') {
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
        $fromCartId = $this->maskedQuoteIdToQuoteId->execute($fromCartId);
        $toCartId = $this->maskedQuoteIdToQuoteId->execute($targetCartId);

        $fromQuote = $this->quoteFactory->create()->load($fromCartId, 'entity_id');
        $fromQuoteItems = $this->getAllVisibleItems($fromQuote);

        $finalQuote = $this->quoteFactory->create()->load($toCartId, 'entity_id');
        $finalQuoteItems = $this->getAllVisibleItems($finalQuote);

        foreach ($fromQuoteItems as $item) {
            $found = false;

            foreach ($finalQuoteItems as $quoteItem) {
                if ($item->getProduct()->getData('sku') === $quoteItem->getProduct()->getData('sku')) {
                    $found = false;

                    $fromQuoteItemPrice = $item->getParentItemId() ? floatval($item->getParentItem()->getPrice()) : floatval($item->getPrice());
                    $quoteItemPrice = $quoteItem->getParentItemId() ? floatval($quoteItem->getParentItem()->getPrice()) : floatval($quoteItem->getPrice());

                    $comparator = new FloatComparator();

                    if ($comparator->equal($quoteItemPrice, $fromQuoteItemPrice)) {
                        $found = true;

                        $quoteItem = $quoteItem->getParentItemId() ? $quoteItem->getParentItem() : $quoteItem;
                        $item = $item->getParentItemId() ? $item->getParentItem() : $item;

                        $quoteItem->setQty($quoteItem->getQty() + $item->getQty());
                        $quoteItem->save();
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
    }
}
