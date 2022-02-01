<?php

namespace Instant\Checkout\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Oauth\Exception;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory as Token;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\StoreManagerInterface;

class AddInstantIntegrationAccountPatch implements DataPatchInterface
{
    const INTEGRATION_NAME = 'Instant Checkout';
    const INSTANT_EMAIL = 'hello@instant.one';
    const DEPENDENCIES = [];
    const ALIASES = [];

    /**
     * @var Token
     */
    private $tokenFactory;
    /**
     * @var AuthorizationService
     */
    private $authorizationService;
    /**
     * @var OauthService
     */
    private $oAuthService;
    /**
     * @var IntegrationFactory
     */
    private $integrationFactory;

    /**
     * AddInstantIntegrationAccountPatch constructor. 
     * This is a data patch that adds the Instant Checkout integration.
     * @param StoreManagerInterface $storeManager
     * @param Token $token
     * @param AuthorizationService $authorizationService
     * @param OauthService $oAuthService
     * @param IntegrationFactory $integrationFactory
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Token $token,
        AuthorizationService $authorizationService,
        OauthService $oAuthService,
        IntegrationFactory $integrationFactory
    ) {
        $this->tokenFactory = $token;
        $this->storeManager = $storeManager;
        $this->authorizationService = $authorizationService;
        $this->oAuthService = $oAuthService;
        $this->integrationFactory = $integrationFactory;
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies()
    {
        return static::DEPENDENCIES;
    }

    /**
     * @return DataPatchInterface|void
     * @throws LocalizedException
     * @throws Exception
     */
    public function apply()
    {
        $integrationExists = $this->integrationFactory->create()->load(static::INTEGRATION_NAME, 'name')->getData();

        if (empty($integrationExists)) {
            $integrationData = array(
                'name' => static::INTEGRATION_NAME,
                'email' => static::INSTANT_EMAIL,
                'status' => '1',
                'endpoint' => '',
                'setup_type' => '0'
            );
            try {
                // Create integration 
                $integration = $this->integrationFactory->create()->setData($integrationData);
                $integration->save();
                $integrationId = $integration->getId();
                $consumerName = 'Integration' . $integrationId;

                // Create consumer
                $oauthService = $this->oAuthService;
                $consumer = $oauthService->createConsumer(['name' => $consumerName]);
                $consumerId = $consumer->getId();
                $integration->setConsumerId($consumer->getId());
                $integration->save();

                // Grant all permissions
                $authorizeService = $this->authorizationService;
                $authorizeService->grantAllPermissions($integrationId);

                // Activate and authorise.
                $token = $this->tokenFactory->create();
                $token->createVerifierToken($consumerId);
                $token->setType('access');
                $token->save();
            } catch (Exception $e) {
                echo 'Error : ' . $e->getMessage();
            }
        }
    }

    /**
     * Get Aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return static::ALIASES;
    }
}
