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
                return ko.toJSON(customerData.get('cart')().subtotalAmount);
            }).subscribe(function () {
                checkoutHelper.handleCartTotalChanged();
            });

            return this;
        },

        render: function () {
            window.onmessage = function (e) {
                if (e.data === 'clearCart') {
                    jQuery.ajax({
                        url: window.location.origin + "/instant/cart/clear",
                        type: 'PUT',
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function () {
                            document.location.reload();
                        },
                        error: function () {
                            this.showErrorAlert();
                            return;
                        }
                    })
                }
            }

            checkoutHelper.handleCartTotalChanged();
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