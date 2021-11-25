define([
    'jquery',
    'checkoutHelper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config, element) {
        $(element).click(function () {
            checkoutHelper.checkoutCustomerCart(
                "#ic-cindex-btn",
                "#ic-cindex-btn-loading",
                "#ic-cindex-btn-text",
                "#ic-cindex-btn-lock-icon",
                "#ic-cindex-desktop-backdrop",
                "#ic-cindex-mobile-backdrop",
                "#ic-cindex-desktop-back-to-checkout",
                "#ic-cindex-mobile-back-to-shopping",
                "checkoutIndex"
            );
        });
    }
});
