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

    function getProduct(productId, selectedOptions, onSuccess) {
        $.ajax({
            type: 'POST',
            url: window.location.origin + "/instant/data/getproduct",
            data: { productId, selectedOptions },
            dataType: 'json',
            retryLimit: 3,
            success: function (data) {
                onSuccess(data);
            },
            error: function () {
                this.retryLimit--;
                if (this.retryLimit) {
                    jQuery.ajax(this);
                }
            }
        })
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
        const formData = parseFormEntries(config.form);
        const product = formData.find(d => d.attribute === 'product');

        getProduct(product.value, null, (data) => {
            const { sku, disabledForSkusContaining } = data;

            let skuIsDisabled = false;
            disabledForSkusContaining.forEach(x => {
                if (x && sku.indexOf(x) !== -1) {
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
            try {
                let formProductId;
                let qty;

                const formSelectedOptions = [];
                const formData = parseFormEntries(config.form);

                formData.forEach((entry) => {
                    const { attribute, value } = entry;

                    const superAttributeRegEx = /super_attribute\[(.*)\]/g;
                    const match = superAttributeRegEx.exec(attribute);

                    if (match && match.length > 0) {
                        formSelectedOptions.push({ attributeId: match[1], optionValue: value });
                    }

                    switch (attribute) {
                        case 'product':
                            formProductId = value;
                        case 'qty':
                            qty = value;
                        default:
                            break;
                    }
                });

                const hasConfigurableAttributes = formSelectedOptions.length > 0;
                const hasIncompleteConfigurableAttributes = !!(formSelectedOptions.filter(o => !o.optionValue).length > 0);
                if (hasConfigurableAttributes && hasIncompleteConfigurableAttributes) {
                    $(pdpRequiredOptionsMsgSelector).css('display', 'unset');
                    return;
                }

                let checkoutWindow;

                if (checkoutHelper.canSetWindowLocation()) {
                    checkoutWindow = checkoutHelper.init();

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
                }

                $(pdpRequiredOptionsMsgSelector).css('display', 'none');
                $(pdpBtnLoadingSelector).css('display', 'unset');
                $(pdpBtnLockIcon).hide();
                $(pdpBtnSelector).css('font-size', '0');
                $(pdpBtnSelector).attr('disabled', true);
                $(pdpBtnText).hide();

                getProduct(formProductId, formSelectedOptions, (data) => {
                    checkoutHelper.handleInstantAwareFunc(() => {
                        const url = checkoutHelper.getCheckoutUrl([{ sku: data.sku, qty }], null, "pdp");

                        if (checkoutWindow) {
                            checkoutWindow.location = url;

                            const loop = setInterval(function () {
                                if (checkoutWindow.closed) {
                                    onClose();
                                    clearInterval(loop);
                                }
                            }, 500);
                        } else {
                            window.location = url;
                        }
                    })
                })
            } catch (err) {
                checkoutHelper.showErrorAlert();
            }

            return false;
        });
    }
});
