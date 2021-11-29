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
 * Class for management of totals information.
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

    public function getProductPrice($productId, $storeCode)
    {
        $storeId = NULL;
        try {
            $store = $this->storeRepository->get($storeCode);
            $storeId = $store->getId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $storeId = NULL;
        }

        $product = $this->productFactory->create();
        if ($storeId) {
            $product = $product->setStoreId($storeId);
        }

        $product = $product->load($productId);

        $originalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();
        $today = time();

        $finalPrice = NULL;

        if ((is_null($specialFromDate) && is_null($specialToDate)) || ($today >= strtotime($specialFromDate) && is_null($specialToDate)) || ($today <= strtotime($specialToDate) && is_null($specialFromDate)) || ($today >= strtotime($specialFromDate) && $today <= strtotime($specialToDate))) {
            $finalPrice = $specialPrice;
        }

        return $finalPrice ? $finalPrice : $originalPrice;
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
        $finalQuote = $this->quoteFactory->create()->load($toCartId, 'entity_id');

        foreach ($fromQuote->getAllVisibleItems() as $item) {
            $found = false;
            foreach ($finalQuote->getAllItems() as $quoteItem) {
                if ($quoteItem->compare($item)) {
                    $found = false;

                    $productId = $quoteItem->getProductId();
                    $quoteItemPrice = floatval($item->getPrice());
                    $productPrice = floatval($this->getProductPrice($productId, $storeCode));

                    $comparator = new FloatComparator();

                    if ($comparator->equal($quoteItemPrice, $productPrice)) {
                        $found = true;
                        $quoteItem->setQty($quoteItem->getQty() + $item->getQty());
                        $quoteItem->save();
                        $finalQuote->itemProcessor->merge($item, $quoteItem);
                    }
                    break;
                }
            }

            if (!$found) {
                $newItem = clone $item;
                $finalQuote->addItem($newItem);
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setParentItem($newItem);
                        $finalQuote->addItem($newChild);
                    }
                }
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
