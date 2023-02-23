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
}
