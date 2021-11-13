<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Customer\Model\Session;

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
        StoreManagerInterface $storeManager,
        Session $customerSession
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;

        return parent::__construct($context);
    }

    /**
     * Get 
     * - App ID 
     * - Store Code
     * - EnableMinicartBtn admin configuration
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $storeCode = $this->storeManager->getStore()->getCode();
        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Helper\Data::class);

        $data['enableMinicartBtn'] = $instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $instantHelper->getInstantAppId();
        $data['enableSandbox'] = $instantHelper->getSandboxEnabledConfig();
        $data['shouldShowInstantBtnForCurrentUser'] = $instantHelper->getShouldShowInstantBtnForCurrentUser();
        $data['storeCode'] = $storeCode;

        $result->setData($data);

        return $result;
    }
}
