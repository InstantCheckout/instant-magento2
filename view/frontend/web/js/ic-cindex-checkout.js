define([
    'jquery',
    'checkoutHelper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config, element) {
        $('#ic-cindex-btn-wrapper').css('display', 'flex');

        const cartBtnWidth = (config.btnWidth && parseInt(config.btnWidth) > 0) ? config.btnWidth : "90";
        const btnBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
        const btnHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

        checkoutHelper.setCartIndexBtnAttributes(cartBtnWidth, btnHeight, btnBorderRadius);

        checkoutHelper.handleInstantAwareFunc(() => {
            checkoutHelper.handleCartTotalChanged();
        })

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
