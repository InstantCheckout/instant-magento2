define([
    'jquery',
    'underscore',
    'checkoutHelper'
], function ($, _, checkoutHelper) {
    "use strict";

    const pdpBtnSelector = "#ic-pdp-btn";
    const pdpRequiredOptionsMsgSelector = "#ic-pdp-required-options-msg";
    const pdpBtnContainerSelector = "#ic-pdp-btn-container";

    return function (config, element) {
        if (config.baseCurrencyCode !== config.currentCurrencyCode) {
            return;
        }

        $(document).on('instant-config-loaded', function () {
            let skuIsDisabled = false;
            window.Instant.disabledForSkusContaining.forEach(x => {
                if (x && config.sku.indexOf(x) !== -1) {
                    skuIsDisabled = true;
                }
            })
            $(pdpBtnContainerSelector).css('display', skuIsDisabled ? 'none' : 'flex');
        });

        checkoutHelper.configurePdpBtn(
            config.shouldResizePdpBtn,
            (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45",
            (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3",
            config.shouldPositionPdpBelowAtc);

        $(pdpBtnContainerSelector).css('display', 'flex');
        $(pdpBtnSelector).prop('disabled', false);
        $(pdpBtnSelector).css('background', config.btnColor);

        const containerStyle = $(pdpBtnContainerSelector).attr('style');
        $(pdpBtnContainerSelector).attr('style', containerStyle + config.pdpBtnCustomStyle);

        $(element).click(function () {
            let qty;
            const options = [];

            const formEntries = [...(new FormData($('#product_addtocart_form')[0]).entries())].map(function (e) {
                return {
                    attribute: e[0],
                    value: e[1],
                }
            });

            formEntries.forEach((entry) => {
                const match = /super_attribute\[(.*)\]/g.exec(entry.attribute);

                if (match && match.length > 0) {
                    options.push({ id: match[1], value: entry.value });
                }

                if (entry.attribute === 'qty') {
                    qty = entry.value;
                }
            });

            if (options.length > 0 && options.filter(o => !o.value).length > 0 ? true : false) {
                $(pdpRequiredOptionsMsgSelector).css('display', 'unset');
                return;
            } else {
                $(pdpRequiredOptionsMsgSelector).css('display', 'none');
            }

            checkoutHelper.checkoutProduct(config.sku, qty, options)
        });
    }
});
