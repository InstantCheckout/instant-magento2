<?php

namespace Instant\Checkout\Setup;

use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

use Psr\Log\LoggerInterface;
use Instant\Checkout\Service\DoRequest;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\TestFramework\Store\StoreManager;

class Recurring implements InstallSchemaInterface
{
    const INTEGRATION_NAME = 'Instant Checkout';

    private $logger;
    private $doRequest;
    private $token;
    private $integrationFactory;
    private $oAuthService;
    private $storeManagerInterface;
    private $configWriter;

    public function __construct(
        LoggerInterface $logger,
        DoRequest $doRequest,
        Token $token,
        IntegrationFactory $integrationFactory,
        OauthService $oAuthService,
        StoreManagerInterface $storeManagerInterface,
        State $state,
        WriterInterface $configWriter
    ) {
        $state->setAreaCode(Area::AREA_FRONTEND);

        $this->logger = $logger;
        $this->doRequest = $doRequest;
        $this->token = $token;
        $this->integrationFactory = $integrationFactory;
        $this->oAuthService = $oAuthService;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->configWriter = $configWriter;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->runAdditionalSetup($this->token);
        $setup->endSetup();
    }

    private function runAdditionalSetup(Token $token)
    {
        $this->logger->debug("==== LOGGING FROM RECURRING SCHEMA =====");

        $instantIntegration = $this->integrationFactory->create()->load(static::INTEGRATION_NAME, 'name')->getData();
        $consumer = $this->oAuthService->loadConsumer($instantIntegration["consumer_id"]);
        $oAuthToken = $this->token->loadByConsumerIdAndUserType($consumer->getId(), 1);
        $baseUrl = $this->storeManagerInterface->getStore()->getBaseUrl();

        $postData = [
            'consumerKey'       => $consumer->getKey(),
            'consumerSecret'    => $consumer->getSecret(),
            'accessToken'       => $oAuthToken->getToken(),
            'accessTokenSecret' => $oAuthToken->getSecret(),
            'platform'          => 'MAGENTO',
            'baseUrl'           => $baseUrl,
            'merchantName'      => 'Magento 123123',
            'email'             => 'test@example.com',
            'isStaging'         => true,
        ];

        $this->logger->debug('==== POST DATA:', $postData);

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

        $this->logger->debug('===== RESPONSE:', (array) $response);

        $this->configWriter->save(InstantHelper::INSTANT_APP_ID_PATH . '_test', $response['merchantId']);
        $this->configWriter->save(InstantHelper::ACCESS_TOKEN_PATH . '_test', $response['accessToken']);
    }
}
