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
    const pdpRequiredOptionsMsgSelector = "#ic-pdp-required-options-msg";
    const pdpBtnContainerSelector = "#ic-pdp-btn-container";

    return function (config, element) {
        $(pdpBtnContainerSelector).css('display', 'flex');
        $(pdpBtnSelector).prop('disabled', false);

        const btnBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
        const btnHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

        checkoutHelper.configurePdpBtn(config.shouldResizePdpBtn, btnHeight, btnBorderRadius);
        checkoutHelper.handleInstantAwareFunc(() => {
            checkoutHelper.handleCartTotalChanged();

            let skuIsDisabled = false;
            Instant.config.disabledForSkusContaining.forEach(x => {
                if (x && config.sku.indexOf(x) !== -1) {
                    skuIsDisabled = true;
                }
            })
            $(pdpBtnContainerSelector).css('display', skuIsDisabled ? 'none' : 'flex');
        })

        $(element).click(function () {
            if (!config.sku) {
                return;
            }

            let qty;
            const options = [];

            parseFormEntries('#product_addtocart_form').forEach((entry) => {
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

            $(pdpRequiredOptionsMsgSelector).css('display', 'none');

            let checkoutWindow;
            if (window.Instant && window.Instant.config) {
                checkoutWindow = checkoutHelper.init([{ sku: config.sku, qty, options }], null, "pdp");
            } else {
                if (!checkoutHelper.canBrowserSetWindowLocation()) {
                    checkoutWindow = checkoutHelper.openCheckoutWindow(checkoutHelper.getInstantBaseUrl());
                }

                checkoutHelper.handleInstantAwareFunc(() => {
                    const url = checkoutHelper.getCheckoutUrl([{ sku: config.sku, qty, options }], null, "pdp");
                    if (checkoutWindow) {
                        checkoutWindow.location = url;
                    } else {
                        window.location = url;
                    }
                });
            }

            if (checkoutWindow) {
                checkoutHelper.showBackdrop(checkoutWindow);
                const loop = setInterval(function () {
                    if (checkoutWindow.closed) {
                        checkoutHelper.hideBackdrop();
                        clearInterval(loop);
                    }
                }, 500);
            }
        });
    }
});
