define([
    'uiComponent',
    'Instant_Checkout/js/ic-helper'
], function (Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },

        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart("checkoutPage");
        },
    });
});
