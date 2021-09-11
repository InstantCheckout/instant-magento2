define([
    'jquery',
    'underscore',
    'checkoutHelper'
], function ($, _, checkoutHelper) {
    "use strict";

    return function (config, element) {
        /**
         * Load Instant Checkout for customer cart
         */
        $(element).click(function () {
            try {
                var form = $(config.form);

                if (!form || !form.valid || !form.valid()) {
                    $('#product-page-select-required-options-msg').css('display', 'unset');
                    return;
                }

                const checkoutWindow = checkoutHelper.openCheckoutWindow();

                $('#product-page-select-required-options-msg').css('display', 'none');
                $('#product-page-instant-btn-loading-indicator').css('display', 'unset');
                $('#product-page-instant-btn-lock-icon').hide();
                $('#product-page-instant-btn').css('font-size', '0');
                $('#product-page-instant-btn').attr('disabled', true);
                $('#product-page-instant-btn-text').hide();
                $('#product-page-instant-backdrop').css('display', 'unset');

                jQuery.ajax({
                    url: "instant/data/getproduct",
                    data: new FormData(form[0]),
                    type: 'POST',
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (data) {
                        checkoutHelper.getCheckoutUrl(_.uniq(data.skuQtyPairs, 'sku'), true, (url) => {
                            checkoutWindow.location = url;

                            $('#product-page-instant-btn-loading-indicator').css('display', 'unset');
                            $('#product-page-back-to-checkout').css('display', 'unset');
                            $('#product-page-back-to-checkout').on('click', function () {
                                checkoutWindow.focus();
                            });

                            const loop = setInterval(function () {
                                if (checkoutWindow.closed) {
                                    $('#product-page-instant-btn').attr('disabled', false);
                                    $('#product-page-instant-btn-text').show();
                                    $('#product-page-instant-btn-loading-indicator').css('display', 'none');
                                    $('#product-page-back-to-checkout').css('display', 'none');
                                    $('#product-page-instant-btn-lock-icon').show();
                                    $('#product-page-instant-btn').css('font-size', '19px');
                                    $('#product-page-instant-backdrop').css('display', 'none');
                                    $('#product-page-instant-btn').attr('data-tooltip', 'Instant Checkout')
                                    clearInterval(loop);
                                }
                            }, 500);
                        })
                    },
                    error: function () {
                        alert("Whoops! An error occurred. Please try again.");
                    }
                })
            } catch (err) {
                alert("Whoops! An error occurred. Please try again.");
            }

            return false;
        });
    }
});
