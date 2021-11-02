<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class GetSimpleProductConfigAttributes extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Configurable
     */
    protected $configurableProduct;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        Configurable $configurableProduct
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;

        return parent::__construct($context);
    }

    /**
     * Given a simple product sku, return config attributes
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $params = $this->getRequest()->getParams();

        // Check if SKU is set.
        if (!isset($params['sku'])) {
            return "Please provide sku in query params";
        }

        // Get configurable product for this simple product
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $childProduct = $this->productRepository->get($params['sku']);

        $productTypeInstance = $objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        $parentIds = $productTypeInstance->getParentIdsByChild($childProduct->getId());
        $parentId = array_shift($parentIds);

        // Get product attributes for this configurable product
        $product = $objectManager->get('\Magento\Catalog\Model\Product')->load($parentId);
        $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($product);
        $productName = $product->getName();

        // Get product model for child product
        $childProduct = $objectManager->get('\Magento\Catalog\Model\Product')->load($childProduct->getId());

        // For each product attribute on configurable product, get value label pairs
        $frontendLabelOptionPairs = [];
        foreach ($productAttributeOptions as $attributeOption) {
            $optionLabel = "";
            $frontendLabelOptionPair = [];
            $childProductAttributeOptionValue = $childProduct->getData($attributeOption['attribute_code']);

            $options = $attributeOption['options'];
            foreach ($options as $option) {
                if ($option['value'] == $childProductAttributeOptionValue) {
                    $optionLabel = $option['label'];
                }
            }

            $frontendLabelOptionPair['label'] = $attributeOption['frontend_label'];
            $frontendLabelOptionPair['value'] = $optionLabel;

            array_push($frontendLabelOptionPairs, $frontendLabelOptionPair);
        }

        $data['optionPairs'] = $frontendLabelOptionPairs;
        $data['name'] = $productName;

        $result->setData($data);

        return $result;
    }
}
