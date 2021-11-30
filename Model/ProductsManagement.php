<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Instant\Checkout\Model;

use Instant\Checkout\Api\ProductsManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;

/**
 * Class for management of totals information.
 */
class ProductsManagement implements ProductsManagementInterface
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
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->quoteFactory = $quoteFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->productFactory = $productFactory;
    }

    /*
    * @param string $storeCode
    * @param string $sku
    * @return string
    */
    public function getPrice($storeCode, $sku)
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

        $product->load($product->getIdBySku($sku));

        $originalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();
        $today = time();

        $finalPrice = $originalPrice;

        $originalPrice = $product->getPrice();

        if (($specialFromDate || $specialToDate) && ($today >= strtotime($specialFromDate) && is_null($specialToDate)) || ($today <= strtotime($specialToDate) && is_null($specialFromDate)) || ($today >= strtotime($specialFromDate) && $today <= strtotime($specialToDate))) {
            $finalPrice = $specialPrice;
        }

        return $finalPrice;
    }
}
