<?php

/**
 * Instant_Checkout
 *
 * @package   Instant_Checkout
 * @author    Instant <hello@instant.one>
 * @copyright 2023 Copyright Instant. https://www.instantcheckout.com.au/
 * @license   https://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      https://www.instantcheckout.com.au/
 */

namespace Instant\Checkout\Service;

use Psr\Log\LoggerInterface;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\OauthService;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\Oauth\TokenFactory;

class InstantIntegrationService
{
    const INTEGRATION_NAME = 'Instant Checkout';
    const INSTANT_EMAIL = 'hello@instant.one';

    private $logger;
    private $integrationFactory;
    private $oAuthService;
    private $authorizationService;
    private $tokenFactory;

    public function __construct(
        LoggerInterface $logger,
        IntegrationFactory $integrationFactory,
        OauthService $oAuthService,
        AuthorizationService $authorizationService,
        TokenFactory $tokenFactory
    ) {
        $this->logger = $logger;
        $this->integrationFactory = $integrationFactory;
        $this->oAuthService = $oAuthService;
        $this->authorizationService = $authorizationService;
        $this->tokenFactory = $tokenFactory;
    }

    public function createInstantIntegration()
    {
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
            $consumer = $this->oAuthService->createConsumer(['name' => $consumerName]);
            $consumerId = $consumer->getId();
            $integration->setConsumerId($consumer->getId());
            $integration->save();

            // Grant all permissions
            $this->authorizationService->grantPermissions($integrationId, $this->getPermissions());

            // Activate and authorise.
            $token = $this->tokenFactory->create();
            $token->createVerifierToken($consumerId);
            $token->setType('access');
            $token->save();
        } catch (\Exception $e) {
            echo 'Error : ' . $e->getMessage();
            $this->logger->error('Instant - Failed to create integration:', (array) $e);
        }
    }

    private function getPermissions()
    {
        return [
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
    }
}
