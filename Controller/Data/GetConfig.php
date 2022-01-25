<?php

namespace Instant\Checkout\Controller\Data;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Customer\Model\Session;

class GetConfig extends Action implements HttpGetActionInterface
{
    protected $jsonResultFactory;
    protected $storeManager;

    /**
     * Constructor.
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

    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $storeCode = $this->storeManager->getStore()->getCode();
        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Helper\Data::class);

        $data['enableMinicartBtn'] = $instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $instantHelper->getInstantAppId();
        $data['enableSandbox'] = $instantHelper->getSandboxEnabledConfig();
        $data['isGuest'] = $instantHelper->getIsGuest();
        $data['disabledTotalThreshold'] = $instantHelper->getDisabledCartTotalThreshold();
        $data['disabledForSkusContaining'] = $instantHelper->getDisabledForSkusContaining();
        $data['storeCode'] = $storeCode;
        $data['mcBtnWidth'] = $instantHelper->getMcBtnWidth();
        $data['cpageBtnWidth'] = $instantHelper->getCPageBtnWidth();
        $data['cindexBtnWidth'] = $instantHelper->getCIndexBtnWidth();
        $data['pdpBtnWidth'] = $instantHelper->getPdpBtnWidth();
        $data['btnBorderRadius'] = $instantHelper->getBtnBorderRadius();
        $data['btnHeight'] = $instantHelper->getBtnHeight();
        $data['checkoutConfig'] = json_decode($instantHelper->getSerializedCheckoutConfig(), true);
        $data['sessId'] = $instantHelper->getSessionId();

        $result->setData($data);

        return $result;
    }
}
