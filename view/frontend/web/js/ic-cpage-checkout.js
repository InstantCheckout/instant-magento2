define([
    'uiComponent',
    'checkoutHelper'
], function (Component, checkoutHelper) {
    'use strict';

    checkoutHelper.refreshInstant();

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        render: function () {
            checkoutHelper.refreshInstant();
        },

        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart("checkoutPage");
        },
    });
});
