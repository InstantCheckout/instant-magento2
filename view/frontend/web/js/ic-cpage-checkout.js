define([
    'uiComponent',
    'checkoutHelper'
], function (Component, checkoutHelper) {
    'use strict';

    checkoutHelper.handleCartTotalChanged();

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        render: function () {
            checkoutHelper.handleCartTotalChanged();
        },

        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart("checkoutPage");
        },
    });
});
