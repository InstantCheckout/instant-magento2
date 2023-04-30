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

namespace Instant\Checkout\Api;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Instant\Checkout\Api\SetCoreConfigInterface;
use Instant\Checkout\Helper\InstantHelper;
use Psr\Log\LoggerInterface;

class SetCoreConfig implements SetCoreConfigInterface
{
    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InstantHelper
     */
    private $instantHelper;

    public function __construct(
        WriterInterface $configWriter,
        LoggerInterface $logger,
        InstantHelper $instantHelper
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->instantHelper = $instantHelper;
    }

    /**
     * Sets the Merchant ID and Access Token in the Magento core config.
     *
     * @api
     * @param string $merchantId
     * @param string $accessToken
     * @return string
     */
    public function setMerchantIdAndAccessToken($merchantId, $accessToken): string
    {
        try {
            $this->configWriter->save(InstantHelper::INSTANT_APP_ID_PATH, $merchantId);
            $this->configWriter->save(InstantHelper::ACCESS_TOKEN_PATH, $accessToken);
            $this->logger->info("Instant => Successfully wrote Merchant ID (" . $merchantId . ") and Access Token (" . $accessToken . ") to core config.");

            $this->instantHelper->clearCache();

            return 'App Id and Access Token set successfully';
        } catch (\Exception $e) {
            $this->logger->error("Instant => ERROR - Failed to write Merchant ID (" . $merchantId . ") and Access Token (" . $accessToken . ") to core config.");
            $this->logger->error($e->__toString());

            return 'Error - Unable to set App Id and Access Token.';
        }
    }
}