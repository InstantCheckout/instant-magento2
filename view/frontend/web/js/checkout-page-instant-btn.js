define([
    'uiComponent',
    'checkoutHelper'
], function (Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/checkout-page-instant-btn'
        },

        /**
         * Load Instant Checkout for customer cart
         */
        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart(
                "#checkout-page-instant-btn",
                "#checkout-page-instant-btn-loading-indicator",
                "#checkout-page-instant-btn-text",
                "#checkout-page-instant-btn-lock-icon",
                "#checkout-page-instant-backdrop",
                "#checkout-page-back-to-checkout"
            );
        },
    });
});
