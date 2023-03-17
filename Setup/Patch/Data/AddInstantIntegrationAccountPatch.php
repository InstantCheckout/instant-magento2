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

use Instant\Checkout\Service\InstantIntegrationService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Oauth\Exception;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Integration\Model\IntegrationFactory;

class AddInstantIntegrationAccountPatch implements DataPatchInterface
{
    const INTEGRATION_NAME = 'Instant Checkout';
    const INSTANT_EMAIL = 'hello@instant.one';
    const DEPENDENCIES = [];
    const ALIASES = [];

    /**
     * @var IntegrationFactory
     */
    private $integrationFactory;

    /**
     * @var InstantIntegrationService
     */
    private $integrationService;

    /**
     * AddInstantIntegrationAccountPatch constructor. 
     * This is a data patch that adds the Instant Checkout integration.
     * 
     * @param IntegrationFactory $integrationFactory
     * @param InstantIntegrationService $integrationService
     */
    public function __construct(
        IntegrationFactory $integrationFactory,
        InstantIntegrationService $integrationService
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->integrationService = $integrationService;
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
            $this->integrationService->createInstantIntegration();
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
