define([
    'jquery',
    'uiComponent',
    'checkoutHelper'
], function ($, Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/minicart-instant-btn',
        },

        initialize: function () {
            this._super();

            return this;
        },

        render: function () {
            jQuery.ajax({
                url: window.location.origin + "/instant/data/getconfig",
                type: 'GET',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    const { enableMinicartBtn } = data;
                    if (parseInt(enableMinicartBtn) === 1) {
                        $('#minicart-instant-btn-container').css('display', 'flex');
                    }
                },
                error: function (jqXHR, textStatus, error) {
                    console.log(jqXHR)
                    alert("Whoops! An error occurred during checkout.");
                }
            })
        },

        /**
         * Load Instant Checkout for customer cart
         */
        checkoutCart: function (element) {
            checkoutHelper.checkoutCustomerCart(
                "#minicart-instant-btn",
                "#minicart-instant-btn-loading-indicator",
                "#minicart-instant-btn-text",
                "#minicart-instant-btn-lock-icon",
                "#minicart-instant-backdrop",
                "#minicart-back-to-checkout"
            );
        }
    });
});