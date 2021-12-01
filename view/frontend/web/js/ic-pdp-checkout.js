define([
    'jquery',
    'underscore',
    'checkoutHelper'
], function ($, _, checkoutHelper) {
    "use strict";

    function parseFormEntries(formSelector) {
        return [...(new FormData($(formSelector)[0]).entries())].map(function (e) {
            return {
                attribute: e[0],
                value: e[1],
            }
        });
    }

    const pdpBtnSelector = "#ic-pdp-btn";
    const pdpBtnLoadingSelector = "#ic-pdp-btn-loading";
    const pdpBtnText = '#ic-pdp-btn-text';
    const pdpBtnLockIcon = "#ic-pdp-btn-lock-icon";
    const pdpDesktopBackdrop = '#ic-pdp-desktop-backdrop';
    const pdpMobileBackdrop = '#ic-pdp-mobile-backdrop';
    const pdpMobileBackToShopping = '#ic-pdp-mobile-back-to-shopping';
    const pdpDesktopBackToCheckout = '#ic-pdp-desktop-back-to-checkout';
    const pdpBtnContainerSelector = "#ic-pdp-btn-container";
    const pdpRequiredOptionsMsgSelector = "#ic-pdp-required-options-msg";

    return function (config, element) {
        checkoutHelper.handleInstantAwareFunc(() => {
            let skuIsDisabled = false;
            window.Instant.config.disabledForSkusContaining.forEach(x => {
                if (x && config.sku.indexOf(x) !== -1) {
                    skuIsDisabled = true;
                }
            })

            $(pdpBtnContainerSelector).css('display', skuIsDisabled ? 'none' : 'flex');
            $(pdpBtnContainerSelector).css('flex-direction', 'column');
        })

        const onClose = () => {
            // Enable the Instant PDP button
            $(pdpBtnSelector).attr('disabled', false);
            $(pdpBtnSelector).css('font-size', '19px');
            $(pdpBtnSelector).attr('data-tooltip', 'Instant Checkout')
            $(pdpBtnText).show();
            $(pdpBtnLoadingSelector).css('display', 'none');
            $(pdpBtnLockIcon).show();

            // Disable backdrop
            $(pdpMobileBackdrop).css('display', 'none');
            $(pdpDesktopBackdrop).css('display', 'none');
        };

        $(element).click(function () {
            if (!config.sku || !config.form) {
                return;
            }

            let qty;
            const options = [];

            parseFormEntries(config.form).forEach((entry) => {
                const { attribute, value } = entry;

                const superAttributeRegEx = /super_attribute\[(.*)\]/g;
                const match = superAttributeRegEx.exec(attribute);

                if (match && match.length > 0) {
                    options.push({ id: match[1], value: value });
                }

                if (attribute === 'qty') {
                    qty = value;
                }
            });

            const hasConfigurableAttributes = options.length > 0;
            const hasIncompleteConfigurableAttributes = options.filter(o => !o.value).length > 0 ? true : false;

            if (hasConfigurableAttributes && hasIncompleteConfigurableAttributes) {
                $(pdpRequiredOptionsMsgSelector).css('display', 'unset');
                return;
            }

            if (checkoutHelper.isClientMobileOrTablet()) {
                $(pdpMobileBackdrop).css('display', 'unset');
                $(pdpMobileBackToShopping).css('display', 'unset');
                $(pdpMobileBackToShopping).on('click', function () {
                    onClose();
                });
            } else {
                $(pdpDesktopBackdrop).css('display', 'unset');
                $(pdpDesktopBackToCheckout).css('display', 'unset');
                $(pdpDesktopBackToCheckout).on('click', function () {
                    checkoutWindow.focus();
                });
            }

            $(pdpRequiredOptionsMsgSelector).css('display', 'none');
            $(pdpBtnLoadingSelector).css('display', 'unset');
            $(pdpBtnLockIcon).hide();
            $(pdpBtnSelector).css('font-size', '0');
            $(pdpBtnSelector).attr('disabled', true);
            $(pdpBtnText).hide();

            const checkoutWindow = checkoutHelper.init([{ sku: config.sku, qty, options }], null, "pdp");
            if (checkoutWindow) {
                const loop = setInterval(function () {
                    if (checkoutWindow.closed) {
                        onClose();
                        clearInterval(loop);
                    }
                }, 500);
            }
        });
    }
});
