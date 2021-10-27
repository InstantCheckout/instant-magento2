define([
    'jquery',
    'underscore',
    'checkoutHelper'
], function ($, _, checkoutHelper) {
    "use strict";

    return function (config, element) {
        $(element).click(function () {
            try {
                const onClose = (isMobile) => {
                    $('#product-page-instant-btn').attr('disabled', false);
                    $('#product-page-instant-btn-text').show();
                    $('#product-page-instant-btn-loading-indicator').css('display', 'none');
                    $('#product-page-back-to-checkout').css('display', 'none');
                    $('#product-page-instant-btn-lock-icon').show();
                    $('#product-page-instant-btn').css('font-size', '19px');
                    $('#product-page-instant-btn').attr('data-tooltip', 'Instant Checkout')

                    if (isMobile) {
                        $('#mobile-product-page-instant-backdrop').css('display', 'none');
                    } else {
                        $('#desktop-product-page-instant-backdrop').css('display', 'none');
                    }
                };

                let formProductId;
                let qty;

                const formSelectedOptions = [];
                const formData = [...(new FormData($(config.form)[0]).entries())].map(function (e) {
                    return {
                        attribute: e[0],
                        value: e[1],
                    }
                })

                formData.forEach((entry) => {
                    const { attribute, value } = entry;

                    const superAttributeRegEx = /super_attribute\[(.*)\]/g;
                    const match = superAttributeRegEx.exec(attribute);

                    if (match && match.length > 0) {
                        formSelectedOptions.push({ attributeId: match[1], optionValue: value });
                    }

                    if (attribute === 'product') {
                        formProductId = value;
                    } else if (attribute === 'qty') {
                        qty = value;
                    }
                });

                const hasConfigurableAttributes = formSelectedOptions.length > 0;
                const hasIncompleteConfigurableAttributes = formSelectedOptions.filter(o => !o.optionValue).length > 0 ? true : false;

                if (hasConfigurableAttributes && hasIncompleteConfigurableAttributes) {
                    // If this is a configurable product, but with incomplete attributes
                    $('#product-page-select-required-options-msg').css('display', 'unset');
                    return;
                }

                var ua = navigator.userAgent || navigator.vendor || window.opera;
                const isFbOrInstaBrowser = (ua.indexOf("FBAN") > -1 || ua.indexOf("FBAV") > -1) || navigator.userAgent.includes("Instagram");

                let checkoutWindow;
                if (!isFbOrInstaBrowser) {
                    checkoutWindow = checkoutHelper.openCheckoutWindow("https://checkout.instant.one/");

                    const isMobile = checkoutHelper.mobileAndTabletCheck();
                    if (isMobile) {
                        $('#mobile-product-page-instant-backdrop').css('display', 'unset');
                        $('#mobile-product-page-back-to-shopping').css('display', 'unset');
                        $('#mobile-product-page-back-to-shopping').on('click', function () {
                            onClose(isMobile);
                        });
                    } else {
                        $('#desktop-product-page-instant-backdrop').css('display', 'unset');
                        $('#desktop-product-page-back-to-checkout').css('display', 'unset');
                        $('#desktop-product-page-back-to-checkout').on('click', function () {
                            checkoutWindow.focus();
                        });
                    }
                }


                $('#product-page-select-required-options-msg').css('display', 'none');
                $('#product-page-instant-btn-loading-indicator').css('display', 'unset');
                $('#product-page-instant-btn-lock-icon').hide();
                $('#product-page-instant-btn').css('font-size', '0');
                $('#product-page-instant-btn').attr('disabled', true);
                $('#product-page-instant-btn-text').hide();

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + "/instant/data/getproduct",
                    data: { productId: formProductId, selectedOptions: formSelectedOptions },
                    dataType: 'json',
                    success: function (data) {
                        checkoutHelper.getCheckoutUrl([{ sku: data.sku, qty }], true, (url) => {
                            if (checkoutWindow) {
                                checkoutWindow.location.replace(url);
                            } else {
                                window.location = url;
                            }


                            const loop = setInterval(function () {
                                if (checkoutWindow.closed) {
                                    onClose();
                                    clearInterval(loop);
                                }
                            }, 500);
                        })
                    },
                    error: function (err) {
                        alert("An error occurred. Please try again.");
                    }
                })
            } catch (err) {
                alert("An error occurred. Please try again.");
            }

            return false;
        });
    }
});
