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

                $permissions = [
                    'Magento_Backend::admin',
                    'Magento_Backend::store',
                    'Magento_Sales::sales',
                    'Magento_Sales::sales_operation',
                    'Magento_Sales::sales_order',
                    'Magento_Sales::actions',
                    'Magento_Sales::create',
                    'Magento_Sales::cancel',
                    'Magento_Sales::actions_view',
                    'Magento_Sales::email',
                    'Magento_Sales::review_payment',
                    'Magento_Sales::capture',
                    'Magento_Sales::invoice',
                    'Magento_Sales::creditmemo',
                    'Magento_Sales::hold',
                    'Magento_Sales::ship',
                    'Magento_Sales::comment',
                    'Magento_Sales::emails',
                    'Magento_Sales::sales_invoice',
                    'Magento_Sales::shipment',
                    'Magento_Sales::transactions',
                    'Magento_Sales::sales_creditmemo',
                    'Magento_Catalog::sets',
                    'Magento_Sales::transactions_fetch',
                    'Magento_Catalog::catalog',
                    'Magento_Catalog::catalog_inventory',
                    'Magento_Catalog::products',
                    'Magento_Catalog::categories',
                    'Magento_Customer::group',
                    'Magento_Customer::customer',
                    'Magento_Customer::manage',
                    'Magento_Customer::actions',
                    'Magento_Customer::online',
                    'Magento_Shipping::shipping_policy',
                    'Magento_Cart::cart',
                    'Magento_Cart::manage',
                    'Magento_Backend::stores',
                    'Magento_Backend::stores_settings',
                    'Magento_Config::config',
                    'Magento_Payment::payment',
                    'Magento_Payment::payment_services',
                    'Magento_Shipping::carriers',
                    'Magento_Shipping::config_shipping',
                    'Magento_Multishipping::config_multishipping',
                    'Magento_Config::config_general',
                    'Magento_Checkout::checkout',
                    'Magento_Swatches::iframe',
                    'Magento_InventoryApi::inventory',
                    'Magento_InventoryApi::source',
                    'Magento_InventoryApi::stock',
                    'Magento_Sales::config_sales',
                    'Magento_InventorySalesApi::stock',
                    'Magento_Tax::manage_tax',
                    'Magento_CurrencySymbol::system_currency',
                    'Magento_CurrencySymbol::currency_rates',
                    'Magento_CurrencySymbol::symbols',
                    'Magento_Backend::stores_attributes',
                    'Magento_Catalog::attributes_attributes',
                ];
                // Grant all permissions
                $authorizeService = $this->authorizationService;
                $authorizeService->grantPermissions($integrationId, $permissions);

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