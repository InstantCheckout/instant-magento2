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

declare(strict_types=1);

namespace Instant\Checkout\Controller\Adminhtml\Activation;

use Instant\Checkout\Service\DoRequest;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Oauth\Exception;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Send
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Send extends Action
{
    const INTEGRATION_NAME = 'Instant Checkout';
    /**
     * @var IntegrationFactory
     */
    protected $integrationFactory;
    /**
     * @var Token
     */
    protected $oauthToken;
    /**
     * @var OauthService
     */
    protected $oauthService;
    /**
     * @var DoRequest
     */
    protected $doRequest;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Send constructor.
     * @param Context $context
     * @param IntegrationFactory $integrationFactory
     * @param Token $oauthToken
     * @param OauthService $oauthService
     * @param DoRequest $request
     * @param ResultJsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Context $context,
        IntegrationFactory $integrationFactory,
        Token $oauthToken,
        OauthService $oauthService,
        DoRequest $doRequest,
        ResultJsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->oauthToken = $oauthToken;
        $this->oauthService = $oauthService;
        $this->doRequest = $doRequest;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute()
    {
        // Load Instant Checkout integration
        $instantIntegration = $this->integrationFactory->create()->load(static::INTEGRATION_NAME, 'name')->getData();

        // Load consumer and access token 
        $consumer = $this->oauthService->loadConsumer($instantIntegration["consumer_id"]);
        $oauthToken = $this->oauthToken->loadByConsumerIdAndUserType($consumer->getId(), 1);

        $consumerKey = $consumer->getKey();
        $consumerSecret = $consumer->getSecret();

        $accessToken = $oauthToken->getToken();
        $accessTokenSecret = $oauthToken->getSecret();

        // Construct payload
        $payload = [
            'baseUrl' => $this->storeManagerInterface->getStore()->getBaseUrl(),
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'accessToken' => $accessToken,
            'accessTokenSecret' => $accessTokenSecret,
        ];

        $valid = 0;
        $responseText = 'failure';

        $response = $this->doRequest->execute('admin/integration/magento', $payload);

        if ($response['status'] === 200) {
            $valid = 1;
            $responseText = 'success';
        }

        $resultJson = $this->resultJsonFactory->create();
        $data = [
            'valid' => $valid,
            'responseText' => $responseText
        ];

        $resultJson->setData($data);
        return $resultJson;
    }
}
