define([
    'uiComponent',
    'checkoutHelper'
], function (Component, checkoutHelper) {
    'use strict';

    checkoutHelper.handleInstantAwareFunc(() => {
        checkoutHelper.handleCartTotalChanged();
    })

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        checkoutCart: function () {
            checkoutHelper.checkoutCustomerCart("checkoutPage");
        },
    });
});
