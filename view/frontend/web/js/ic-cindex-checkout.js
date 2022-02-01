define([
    'jquery',
    'checkoutHelper'
], function ($, checkoutHelper) {
    "use strict";

    return function (config, element) {
        const cindexBtnWrapperSelector = "#ic-cindex-btn-wrapper";

        const btnBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
        const btnHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

        checkoutHelper.setCartIndexBtnAttributes(config.shouldResizeCartIndexBtn, btnHeight, btnBorderRadius);
        $(cindexBtnWrapperSelector).css('display', 'flex');

        $(element).click(function () {
            checkoutHelper.checkoutCustomerCart("checkoutIndex");
        });
    }
});
