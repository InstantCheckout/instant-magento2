<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

class GetConfig extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor.
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;

        return parent::__construct($context);
    }

    /**
     * Get 
     * - App ID 
     * - Store Code
     * - EnableMinicartBtn admin configuration
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $storeCode = $this->storeManager->getStore()->getCode();
        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Helper\Data::class);

        $data['enableMinicartBtn'] = $instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $instantHelper->getInstantAppId();
        $data['storeCode'] = $storeCode;

        $result->setData($data);

        return $result;
    }
}
