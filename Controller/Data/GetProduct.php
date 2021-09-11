<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Convert\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class GetProduct extends Action implements HttpPostActionInterface
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Cart\RequestInfoFilterInterface
     */
    private $requestInfoFilter;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Constructor.
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry,
        ManagerInterface $eventManager
    ) {
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->eventManager = $eventManager;

        return parent::__construct($context);
    }

    /**
     * Get Store code
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Get product object based on requested product information
     */
    protected function getProduct($productInfo)
    {
        $product = null;
        if ($productInfo instanceof Product) {
            $product = $productInfo;
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The product wasn't found. Verify the product and try again.")
                );
            }
        } elseif (is_int($productInfo) || is_string($productInfo)) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($productInfo, false, $storeId);
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The product wasn't found. Verify the product and try again."),
                    $e
                );
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("The product wasn't found. Verify the product and try again.")
            );
        }
        $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
        if (!is_array($product->getWebsiteIds()) || !in_array($currentWebsiteId, $product->getWebsiteIds())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("The product wasn't found. Verify the product and try again.")
            );
        }
        return $product;
    }


    /**
     * Get request quantity
     */
    private function getQtyRequest($product, $request = 0)
    {
        $request = $this->getProductRequest($request);
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minimumQty = $stockItem->getMinSaleQty();

        if (
            $minimumQty
            && $minimumQty > 0
            && !$request->getQty()
        ) {
            $request->setQty($minimumQty);
        }

        return $request;
    }

    /**
     * Initialise product
     */
    protected function initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');

        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Getter for RequestInfoFilter
     */
    private function getRequestInfoFilter()
    {
        if ($this->requestInfoFilter === null) {
            $this->requestInfoFilter = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Checkout\Model\Cart\RequestInfoFilterInterface::class);
        }
        return $this->requestInfoFilter;
    }

    /**
     * Get request for product add to cart procedure
     */
    protected function getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof \Magento\Framework\DataObject) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new \Magento\Framework\DataObject(['qty' => $requestInfo]);
        } elseif (is_array($requestInfo)) {
            $request = new \Magento\Framework\DataObject($requestInfo);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }
        $this->getRequestInfoFilter()->filter($request);

        return $request;
    }

    /**
     * Get skuQtyPairs for product page form
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['qty'])) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->_objectManager->get(
                    \Magento\Framework\Locale\ResolverInterface::class
                )->getLocale()]
            );
            $params['qty'] = $filter->filter($params['qty']);
        }

        $product = $this->initProduct();
        $product = $this->getProduct($product);
        $request = $this->getQtyRequest($product, $params);

        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = $this->objectFactory->create(['qty' => $request]);
        }

        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($request, $product, null);

        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $parentItem = null;
        $skuQtyPairs = [];

        foreach ($cartCandidates as $candidate) {
            $skuQtyPair = [];

            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);

            $product->setOptions($candidate->getCustomOptions());
            $product->setProduct($candidate);

            $skuQtyPair['sku'] = $product->getSku();
            $skuQtyPair['qty'] = $product->getQty();

            array_push($skuQtyPairs, $skuQtyPair);
        }

        $result = $this->jsonResultFactory->create();
        $storeCode = $this->getStoreCode();
        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Helper\Data::class);

        $data = [];
        $data['skuQtyPairs'] = $skuQtyPairs;
        $data['storeCode'] = $storeCode;
        $data['appId'] = $instantHelper->getInstantAppId();

        $result->setData($data);

        return $result;
    }
}
