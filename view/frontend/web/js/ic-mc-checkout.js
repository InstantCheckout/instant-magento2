define([
    'ko',
    'jquery',
    'uiComponent',
    'checkoutHelper',
    'Magento_Customer/js/customer-data',
], function (ko, $, Component, checkoutHelper, customerData) {
    'use strict';

    const mcBtnContainerSelector = "#ic-mc-btn-container";
    $(mcBtnContainerSelector).css('display', 'flex');

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-mc-btn',
        },

        initialize: function () {
            this._super();

            ko.computed(function () {
                return ko.toJSON(customerData.get('cart')().subtotalAmount);
            }).subscribe(function () {
                if (customerData.get('cart')().subtotalAmount) {
                    checkoutHelper.reloadInstantConfig();
                }
            });

            return this;
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },

        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart("minicart");
        }
    });
});