define([
    'jquery',
    'underscore',
    'Instant_Checkout/js/ic-helper'
], function ($, _) {
    "use strict";

    const pdpBtnSelector = "#ic-pdp-btn";
    const pdpBtnContainerSelector = "#ic-pdp-btn-container";
    const pdpBtnOrStrike = '#ic-pdp-btn-strike'
    const atcBtnSelector = '#product-addtocart-button';

    window.MutationObserver = window.MutationObserver
        || window.WebKitMutationObserver
        || window.MozMutationObserver;

    const syncInstantButtonDisabled = () => {
        const atcDisabledValue = $('#product-addtocart-button').is(':disabled');
        $(pdpBtnSelector).prop('disabled', atcDisabledValue);
    };
    syncInstantButtonDisabled();

    new MutationObserver(function () {
        syncInstantButtonDisabled();
    }).observe(document.querySelector('#product-addtocart-button'), {
        attributes: true
    });

    return function (config, element) {
        $(pdpBtnContainerSelector).css('display', 'flex');

        $(document).on('instant-config-loaded', function () {
            let skuIsDisabled = false;
            window.Instant.disabledForSkusContaining.forEach(x => {
                if (x && config.sku.indexOf(x) !== -1) {
                    skuIsDisabled = true;
                }
            })
            $(pdpBtnContainerSelector).css('display', skuIsDisabled ? 'none' : 'flex');
        });

        // If we should reposition OR strike, or should reposition pdp below atc, then move the OR strike before button
        if (config.pdpShouldRepositionOrStrikeAboveBtn || config.shouldPositionPdpBelowAtc) {
            $(pdpBtnOrStrike).insertBefore(pdpBtnSelector);
            $(pdpBtnSelector).css('margin-bottom', '0px');
            $(pdpBtnSelector).css('margin-top', '5px');
        }

        // If we should resize pdp button
        // We resize it to the size of the add to cart button
        if (config.shouldResizePdpBtn) {
            $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
            $(window).resize(function () {
                $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
            });
        }

        // Apply any custom styles for button specified in config
        if (config.pdpBtnCustomStyle) {
            const btnStyle = $(pdpBtnSelector).attr('style');
            $(pdpBtnSelector).attr('style', btnStyle ? btnStyle + config.pdpBtnCustomStyle : config.pdpBtnCustomStyle);
        }

        // Apply any custom styles for container specified in config
        if (config.pdpBtnContainerCustomStyle) {
            const containerStyle = $(pdpBtnContainerSelector).attr('style');
            $(pdpBtnContainerSelector).attr('style', containerStyle ? containerStyle + config.pdpBtnContainerCustomStyle : config.pdpBtnContainerCustomStyle);
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

        $(element).click(function () {
            window.InstantM2.handlePdpBtnClicked(config.sku);
        });
    }
});
