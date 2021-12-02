<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class GetProduct extends Action implements HttpGetActionInterface
{
    protected $jsonResultFactory;
    protected $productRepository;
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

    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $params = $this->getRequest()->getParams();

        $options = NULL;

        if (!isset($params['sku'])) {
            return "Please provide sku in query params";
        }

        if (isset($params['options'])) {
            $options = $params['options'];
            $options = json_decode(urldecode($options), true);
        }

        if (is_countable($options) && count($options) > 0) {
            $productOptions = [];

            $product = $this->productRepository->get($params['sku']);
            foreach ($options as $option) {
                $id = $option['id'];
                $value = $option['value'];
                $productOptions[$id] = $value;
            }

            $product = $this->configurableProduct->getProductByAttributes($productOptions, $product);
        } else {
            // If selectedOptions is not populated, then we have a simple product with no config
            $product = $this->productRepository->get($params['sku']);
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
