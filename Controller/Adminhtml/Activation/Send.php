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
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

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
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;


    /**
     * Send constructor.
     * @param Context $context
     * @param IntegrationFactory $integrationFactory
     * @param Token $oauthToken
     * @param OauthService $oauthService
     * @param DoRequest $request
     * @param ResultJsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        IntegrationFactory $integrationFactory,
        Token $oauthToken,
        OauthService $oauthService,
        DoRequest $doRequest,
        ResultJsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManagerInterface,
        WriterInterface $configWriter,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->integrationFactory = $integrationFactory;
        $this->oauthToken = $oauthToken;
        $this->oauthService = $oauthService;
        $this->doRequest = $doRequest;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->getAppIdAndTokenFromBackend();
    }

    // TODO: Return an array for all stores?
    private function getStoreEmail(): string
    {
        return $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getAppIdAndTokenFromBackend()
    {
        $instantIntegration = $this->integrationFactory->create()->load(static::INTEGRATION_NAME, 'name')->getData();
        $consumer = $this->oauthService->loadConsumer($instantIntegration["consumer_id"]);
        $token = $this->oauthToken->loadByConsumerIdAndUserType($consumer->getId(), 1);
        $store = $this->storeManagerInterface->getStore();

        $postData = [
            'consumerKey'       => $consumer->getKey(),
            'consumerSecret'    => $consumer->getSecret(),
            'accessToken'       => $token->getToken(),
            'accessTokenSecret' => $token->getSecret(),
            'platform'          => 'MAGENTO',
            'baseUrl'           => $store->getBaseUrl(),
            'merchantName'      => $store->getId(),
            'email'             => $this->getStoreEmail(),
            'isStaging'         => true,
        ];

        try {
            $response = $this->doRequest->execute(
                'admin/extension/activate',
                $postData,
                'POST',
                -1,
                0,
                true,
                true,
                'https://gqqe5b9w1m.execute-api.ap-southeast-2.amazonaws.com/pr725/admin/extension/activate',
            );

            $responseJson = json_decode($response['result'], true);

            if (array_key_exists('error', $responseJson)) {
                throw new \Exception('Error in response: ' . $responseJson['message']);
            }

            if (empty($responseJson['merchantId']) || empty($responseJson['accessToken'])) {
                throw new \Exception('No merchantId or accessToken in response. Unable to set config.');
            }

            $this->configWriter->save(InstantHelper::INSTANT_APP_ID_PATH, $responseJson['merchantId']);
            $this->configWriter->save(InstantHelper::ACCESS_TOKEN_PATH, $responseJson['accessToken']);
            $this->logger->info('Instant: Success! MerchantID (' . $responseJson['merchantId'] . ') and AccessToken (' . $responseJson['accessToken']  . ') were set in core config.');
        } catch (\Exception $e) {
            $this->logger->critical('Instant - Unable to set App ID and Access Token. Check the POST request for errors.');
            $this->logger->critical($e->__toString());
        }
    }
}
