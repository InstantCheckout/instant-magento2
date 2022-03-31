define([
    'jquery',
    'underscore',
    'checkoutHelper'
], function ($, _, checkoutHelper) {
    "use strict";

    const pdpBtnSelector = "#ic-pdp-btn";
    const pdpRequiredOptionsMsgSelector = "#ic-pdp-required-options-msg";
    const pdpBtnContainerSelector = "#ic-pdp-btn-container";
    const pdpBtnOrStrike = '#ic-pdp-btn-strike'

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
            (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3");

        // Apply any custom styles specified in config
        if (config.pdpBtnCustomStyle) {
            const containerStyle = $(pdpBtnContainerSelector).attr('style');
            $(pdpBtnContainerSelector).attr('style', containerStyle ? containerStyle : '' + config.pdpBtnCustomStyle);
        }

        // If we should reposition OR strike, or should reposition pdp below atc, then move the OR strike before button
        if (config.pdpShouldRepositionOrStrikeAboveBtn || config.shouldPositionPdpBelowAtc) {
            $(pdpBtnOrStrike).insertBefore(pdpBtnSelector);
            $(pdpBtnSelector).css('margin-bottom', '0px');
            $(pdpBtnSelector).css('margin-top', '5px');
        }

        // If we should position pdp below atc, then do not attempt to prepend pdp btn above actions
        if (!config.shouldPositionPdpBelowAtc) {
            $(pdpBtnContainerSelector).prependTo($(".box-tocart .fieldset .actions"));
        }

        // If specified, reposition the pdp button div
        if (config.pdpBtnRepositionDiv) {
            const [position, selector] = config.pdpBtnRepositionDiv.split('|');
            if (position === 'AFTER') {
                $(pdpBtnContainerSelector).insertAfter(selector);
            } else if (position === 'BEFORE') {
                $(pdpBtnContainerSelector).insertBefore(selector);
            }
        }

        // If specified, reposition the pdp button within a div
        if (config.pdpBtnRepositionWithinDiv) {
            const [position, selector] = config.pdpBtnRepositionWithinDiv.split('|');
            if (position === 'PREPEND') {
                $(pdpBtnContainerSelector).prependTo(selector);
            } else if (position === 'APPEND') {
                $(pdpBtnContainerSelector).appendTo(selector);
            }
        }

        $(pdpBtnContainerSelector).css('display', 'flex');
        $(pdpBtnSelector).prop('disabled', false);
        $(pdpBtnSelector).css('background', config.btnColor);

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

            qty = qty ? qty : "1" // If qty is not specified, then assume qty: 1

            checkoutHelper.checkoutProduct(config.sku, qty, options)
        });
    }
});
