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
     * @var JsonResult
     */
    protected $jsonResultFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

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

        return parent::__construct($context);
    }

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
