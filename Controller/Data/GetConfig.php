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

use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Service\DoRequest;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\JsonHexTag;

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
     * @var CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * @var DoRequest
     */
    private $doRequest;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        CompositeConfigProvider $configProvider,
        InstantHelper $instantHelper,
        DoRequest $doRequest,
        SerializerInterface $serializerInterface = null
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->serializer = $serializerInterface ?: ObjectManager::getInstance()
            ->get(JsonHexTag::class);
        $this->instantHelper = $instantHelper;
        $this->doRequest = $doRequest;

        return parent::__construct($context);
    }

    /**
     * Retrieve serialized checkout config.
     *
     * @return bool|string
     */
    public function getSerializedCheckoutConfig()
    {
        try {
            return  $this->serializer->serialize($this->configProvider->getConfig());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $storeCode = $this->storeManager->getStore()->getCode();

        $data['enableMinicartBtn'] = $this->instantHelper->getInstantMinicartBtnEnabled();
        $data['appId'] = $this->instantHelper->getInstantAppId();
        $data['enableSandbox'] = $this->instantHelper->getSandboxEnabledConfig();
        $data['isGuest'] = $this->instantHelper->getIsGuest();
        $data['disabledForSkusContaining'] = $this->instantHelper->getDisabledForSkusContaining();
        $data['storeCode'] = $storeCode;
        $data['mcBtnWidth'] = $this->instantHelper->getMcBtnWidth();
        $data['cpageBtnWidth'] = $this->instantHelper->getCPageBtnWidth();
        $data['shouldResizeCartIndexBtn'] = $this->instantHelper->getShouldResizeCartIndexBtn();
        $data['shouldResizePdpBtn'] = $this->instantHelper->getShouldResizePdpBtn();
        $data['btnBorderRadius'] = $this->instantHelper->getBtnBorderRadius();
        $data['btnHeight'] = $this->instantHelper->getBtnHeight();
        $data['btnColor'] = $this->instantHelper->getBtnColor();
        $data['checkoutConfig'] = json_decode($this->getSerializedCheckoutConfig(), true);

        // If cookie forwarding is enabled, then generate sessionId, retrieve cookies and make call to Instant.
        if ($this->instantHelper->getCookieForwardingEnabled()) {
            $instantSessionId = $this->instantHelper->guid();
            $data['sessId'] = $instantSessionId;

            $cookieStr = '';
            foreach ($_COOKIE as $cookieKey => $cookieValue) {
                $cookieStr = $cookieStr . $cookieKey . '=' . $this->instantHelper->encodeURIComponent($cookieValue) . '; ';
            }

            $payload = [
                'cookie' => substr(trim($cookieStr), 0, -1),
                'id' => $instantSessionId,
                'storeCode' => $storeCode
            ];
            $this->doRequest->execute('session', $payload, 'POST', -1, 0, false, false);
        }

        $result->setData($data);
        return $result;
    }
}
