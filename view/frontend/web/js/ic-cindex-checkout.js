define([
    'jquery',
    'Instant_Checkout/js/ic-helper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config) {
        const currencyEnabled = ((window.Instant.enableMulticurrencyOnSingleStore && (window.Instant.baseCurrencyCode !== window.Instant.currentCurrencyCode)) || (window.Instant.baseCurrencyCode === window.Instant.currentCurrencyCode));
        if (!currencyEnabled) {
            return;
        }

        $(document).on('instant-config-loaded', function () {
            checkoutHelper.setCartIndexBtnAttributes(config.shouldResizeCartIndexBtn);
        });
    }
});
