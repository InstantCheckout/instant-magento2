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
use Magento\Framework\Controller\Result\JsonFactory;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Api\StoreRepositoryInterface;

class GetProduct extends Action
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
     * @var Configurable
     */
    protected $configurableProduct;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        Configurable $configurableProduct,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->storeRepository = $storeRepository;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $params = $this->getRequest()->getParams();

        $options = NULL;
        $storeId = NULL;
        $frontendLabelOptionPairs = NULL;

        if (!isset($params['sku'])) {
            return "Please provide sku in query params";
        }

        if (!isset($params['storeCode'])) {
            return "Please provide storeCode in query params";
        }

        if (isset($params['options'])) {
            $options = $params['options'];
            $options = json_decode(urldecode($options), true);
        }

        try {
            $store = $this->storeRepository->get($params['storeCode']);
            $storeId = $store->getId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $storeId = NULL;
        }

        /* Polyfill for < PHP 7.3 */
        if (!function_exists('is_countable')) {
            function is_countable($c)
            {
                return is_array($c) || $c instanceof Countable;
            }
        }

        if (is_countable($options) && count($options) > 0) {
            $productOptions = [];

            $product = $this->productRepository->get($params['sku'], false, $storeId);
            foreach ($options as $option) {
                $id = $option['id'];
                $value = $option['value'];
                $productOptions[$id] = $value;
            }

            $product = $this->configurableProduct->getProductByAttributes($productOptions, $product);
        } else {
            // If selectedOptions is not populated, then we have a simple product with no config
            $product = $this->productRepository->get($params['sku'], false, $storeId);
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productTypeInstance = $objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        $parentIds = $productTypeInstance->getParentIdsByChild($product->getId());
        $parentId = array_shift($parentIds);

        // Get product attributes for this configurable product
        $configurableProduct = $objectManager->get('\Magento\Catalog\Model\Product')->load($parentId);
        $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($configurableProduct);

        // Get product model for child product
        $childProduct = $objectManager->get('\Magento\Catalog\Model\Product')->load($product->getId());

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

        $mediaGalleryEntries = [];
        $customAttributes = [];

        foreach ($product->getMediaGalleryEntries() as $entry) {
            $image = [];
            $image['media_type'] = $entry->getMediaType();
            $image['file'] = $entry->getFile();
            $mediaGalleryEntries[] = $image;
        }

        foreach ($product->getCustomAttributes() as $attributeEntry) {
            $attribute = [];
            $attribute['attribute_code'] = $attributeEntry->getAttributeCode();
            $attribute['value'] = $attributeEntry->getValue();
            $customAttributes[] = $attribute;
        }

        if ($frontendLabelOptionPairs) {
            $data['frontend_options'] = $frontendLabelOptionPairs;
        }

        $data['id'] = $product->getId();
        $data['attribute_set_id'] = $product->getAttributeSetId();
        $data['sku'] = $product->getSku();
        $data['price'] = $product->getPrice();
        $data['name'] = $product->getName();
        $data['custom_attributes'] = $customAttributes;
        $data['media_gallery_entries'] = $mediaGalleryEntries;

        $result->setData($data);

        return $result;
    }
}
