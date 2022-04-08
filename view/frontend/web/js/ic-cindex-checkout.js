define([
    'jquery',
    'Instant_Checkout/js/ic-helper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config, element) {
        if (config.baseCurrencyCode !== config.currentCurrencyCode) {
            return;
        }

        $(document).on('instant-config-loaded', function () {
            checkoutHelper.setCartIndexBtnAttributes(
                config.shouldResizeCartIndexBtn,
                (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45",
                (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3",
                config.btnColor);
        })

        $(element).click(function () {
            checkoutHelper.checkoutCustomerCart("checkoutIndex");
        });
    }
});
