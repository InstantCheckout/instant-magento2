<?php

namespace Instant\Checkout\Controller\Data;

use Exception;
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
     * @var \Magento\Checkout\Model\CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->serializer = $serializerInterface ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\JsonHexTag::class);

        return parent::__construct($context);
    }

    /**
     * Retrieve serialized checkout config.
     *
     * @return bool|string
     * @since 100.2.0
     */
    public function getSerializedCheckoutConfig()
    {
        try {
            return  $this->serializer->serialize($this->configProvider->getConfig());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $storeCode = $this->storeManager->getStore()->getCode();
        $instantHelper = $this->_objectManager->create(\Instant\Checkout\Model\Config\InstantConfig::class);
        $doRequest = $this->_objectManager->create(\Instant\Checkout\Service\DoRequest::class);

        $data['enableMinicartBtn'] = $instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $instantHelper->getInstantAppId();
        $data['enableSandbox'] = $instantHelper->getSandboxEnabledConfig();
        $data['isGuest'] = $instantHelper->getIsGuest();
        $data['disabledForSkusContaining'] = $instantHelper->getDisabledForSkusContaining();
        $data['storeCode'] = $storeCode;
        $data['mcBtnWidth'] = $instantHelper->getMcBtnWidth();
        $data['cpageBtnWidth'] = $instantHelper->getCPageBtnWidth();
        $data['shouldResizeCartIndexBtn'] = $instantHelper->getShouldResizeCartIndexBtn();
        $data['shouldResizePdpBtn'] = $instantHelper->getShouldResizePdpBtn();
        $data['btnBorderRadius'] = $instantHelper->getBtnBorderRadius();
        $data['btnHeight'] = $instantHelper->getBtnHeight();
        $data['checkoutConfig'] = json_decode($this->getSerializedCheckoutConfig(), true);

        $instantSessionId = $instantHelper->guid();
        $data['sessId'] = $instantSessionId;

        $cookieStr = '';

        try {
            foreach ($_COOKIE as $cookieKey => $cookieValue) {
                $cookieStr = $cookieStr . $cookieKey . '=' . $instantHelper->encodeURIComponent($cookieValue) . '; ';
            }

            $payload = [
                'cookie' => substr(trim($cookieStr), 0, -1),
                'id' => $instantSessionId,
                'storeCode' => $storeCode
            ];

            $doRequest->execute('/session', $payload);
        } catch (Exception $e) {
            // 
        }
        $result->setData($data);

        return $result;
    }
}
