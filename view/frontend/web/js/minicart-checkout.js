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
            template: 'Instant_Checkout/minicart-instant-btn',
        },

        initialize: function () {
            this._super();

            ko.computed(function () {
                return ko.toJSON(customerData.get('cart')().subtotal);
            }).subscribe(function () {
                checkoutHelper.handleMinicartBtnRender();
            });

            return this;
        },

        render: function () {
            checkoutHelper.handleMinicartBtnRender();
        },

        /**
         * Load Instant Checkout for customer cart
         */
        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart(
                "#minicart-instant-btn",
                "#minicart-instant-btn-loading-indicator",
                "#minicart-instant-btn-text",
                "#minicart-instant-btn-lock-icon",
                "#desktop-minicart-instant-backdrop",
                "#mobile-minicart-instant-backdrop",
                "#desktop-minicart-back-to-checkout",
                "#mobile-minicart-back-to-checkout",
                "minicart"
            );
        }
    });
});