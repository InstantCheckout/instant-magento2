<?php

namespace Instant\Checkout\Api;

interface ServiceInterface
{
    /**
     * Handle failed payment
     *
     * @api
     * @return string Redirect Url
     */
    public function handle_failed_payment();

    /**
     * Update the App ID and Access Token when the activate button is clicked.
     *
     * @api
     * @return string Redirect Url
     */
    public function set_app_id_and_access_token(array $appIdAndAccessToken);
}
