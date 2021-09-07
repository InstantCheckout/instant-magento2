define([
    'jquery',
    'checkoutHelper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config, element) {
        /**
         * Load Instant Checkout for customer cart
         */
        $(element).click(function () {
            checkoutHelper.checkoutCustomerCart(
                "#checkout-index-page-instant-btn",
                "#checkout-index-page-instant-btn-loading-indicator",
                "#checkout-index-page-instant-btn-text",
                "#checkout-index-page-instant-btn-lock-icon",
                "#checkout-index-page-instant-backdrop",
                "#checkout-index-page-back-to-checkout"
            );
        });
    }
});
