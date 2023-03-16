<?php

namespace Instant\Checkout\Api;

interface SetCoreConfigInterface
{
    /**
     * Sets the Merchant ID and Access Token in the Magento core config.
     *
     * @api
     * @param string $merchantId
     * @param string $accessToken
     * @return boolean
     */
    public function setMerchantIdAndAccessToken($merchantId, $accessToken);
}
