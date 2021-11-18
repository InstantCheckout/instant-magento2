<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Configurable
     */
    private $configurableProduct;

    /**
     * Constructor.
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        Configurable $configurableProduct
    ) {
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->request = $request;
        $this->configurableProduct = $configurableProduct;

        return parent::__construct($context);
    }

    /**
     * Get skuQtyPairs for product page form
     */
    public function execute()
    {
        $params = $this->request->getPost();

        $productId = $params['productId'];
        $selectedOptions = $params['selectedOptions'];

        $options = [];
        $product = NULL;

        if (is_countable($selectedOptions) && count($selectedOptions) > 0) {
            // If selectedOptions is populated, then we have a configurable product
            $product = $this->productRepository->getById($productId);
            foreach ($selectedOptions as $selectedOption) {
                $attributeId = $selectedOption['attributeId'];
                $optionValue = $selectedOption['optionValue'];
                $options[$attributeId] = $optionValue;
            }

            $product = $this->configurableProduct->getProductByAttributes($options, $product);
        } else {
            // If selectedOptions is not populated, then we have a simple product with no config
            $product = $this->productRepository->getById($productId);
        }

        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Helper\Data::class);

        $result = $this->jsonResultFactory->create();
        $data = [];
        $data['sku'] = $product->getSku();
        $data['disabledForSkusContaining'] = $instantHelper->getDisabledForSkusContaining();
        $data['disabledTotalThreshold'] = $instantHelper->getDisabledCartTotalThreshold();
        $data['productPageAddToCartFormId'] = $instantHelper->getProductPageAddToCartFormId();

        $result->setData($data);

        return $result;
    }
}
