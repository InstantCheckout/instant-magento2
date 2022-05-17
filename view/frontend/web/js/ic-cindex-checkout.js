define([
    'jquery',
    'Instant_Checkout/js/ic-helper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config) {
        if (config.baseCurrencyCode !== config.currentCurrencyCode) {
            return;
        }

        $(document).on('instant-config-loaded', function () {
            checkoutHelper.setCartIndexBtnAttributes(config.shouldResizeCartIndexBtn);
        });
    }
});
