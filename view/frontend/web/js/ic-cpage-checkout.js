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
            checkoutHelper.checkoutCustomerCart(
                "#ic-cpage-btn",
                "#ic-cpage-btn-loading",
                "#ic-cpage-btn-text",
                "#ic-cpage-btn-lock-icon",
                "#ic-cpage-desktop-backdrop",
                "#ic-cpage-mobile-backdrop",
                "#ic-cpage-desktop-back-to-checkout",
                "#ic-cpage-mobile-back-to-shopping",
                "checkoutPage"
            );
        },
    });
});
