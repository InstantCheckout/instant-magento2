<?php

namespace Instant\Checkout\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

use Psr\Log\LoggerInterface;
use Instant\Checkout\Service\DoRequest;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\StoreManagerInterface;

class Recurring implements InstallSchemaInterface
{
    const INTEGRATION_NAME = 'Instant Checkout';

    private $logger;
    private $doRequest;
    private $token;
    private $integrationFactory;
    private $oAuthService;
    private $storeManagerInterface;

    public function __construct(
        LoggerInterface $logger,
        DoRequest $doRequest,
        Token $token,
        IntegrationFactory $integrationFactory,
        OauthService $oAuthService,
        StoreManagerInterface $storeManagerInterface,
    ) {
        $this->logger = $logger;
        $this->doRequest = $doRequest;
        $this->token = $token;
        $this->integrationFactory = $integrationFactory;
        $this->oAuthService = $oAuthService;
        $this->storeManagerInterface = $storeManagerInterface;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();


        $this->logger->debug("==== LOGGING FROM RECURRING SCHEMA =====");
        $this->runAdditionalSetup($this->token);

        $setup->endSetup();
    }

    private function runAdditionalSetup(Token $token)
    {
        $instantIntegration = $this->integrationFactory->create()->load(static::INTEGRATION_NAME, 'name')->getData();
        $consumer = $this->oAuthService->loadConsumer($instantIntegration["consumer_id"]);

        $baseUrl = $this->storeManagerInterface->getStore()->getBaseUrl();

        // Call new Instant Endpoint
        $response = $this->doRequest->execute(
            'https://gqqe5b9w1m.execute-api.ap-southeast-2.amazonaws.com/pr725/admin/extension/activate',
            [
                'consumerKey'       => $consumer->getKey(),
                'consumerSecret'    => $consumer->getSecret(),
                'accessToken'       => $token->getToken(),
                'accessTokenSecret' => $token->getSecret(),
                'platform'          => 'MAGENTO',
                'baseUrl'           => $baseUrl,
                'merchantName'      => 'Magento Test Merchant',
                'email'             => 'test@example.com',
                'isStaging'         => true,
            ],
        );

        $this->logger->debug('===== RESPONSE:', (array) $response);

        /* 
                 $response returns:
                 {
                    appId: string,
                    accessToken: string
                 }
                */

        // TODO: Commit appId and accessToken to M2 config
    }
}
