define([
    'ko',
    'jquery',
    'uiComponent',
    'checkoutHelper',
    'Magento_Customer/js/customer-data',
], function (ko, $, Component, checkoutHelper, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-mc-btn',
        },

        initialize: function () {
            this._super();

            ko.computed(function () {
                return ko.toJSON(customerData.get('cart')().subtotalAmount);
            }).subscribe(function () {
                checkoutHelper.handleCartTotalChanged();
            });

            return this;
        },

        render: function () {
            checkoutHelper.handleCartTotalChanged();
        },

        /**
         * Load Instant Checkout for customer cart
         */
        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart(
                "#ic-mc-btn",
                "#ic-mc-btn-loading",
                "#ic-mc-btn-text",
                "#ic-mc-btn-lock-icon",
                "#ic-mc-desktop-backdrop",
                "#ic-mc-mobile-backdrop",
                "#ic-mc-desktop-back-to-checkout",
                "#ic-mc-minicart-back-to-shopping",
                "minicart"
            );
        }
    });
});