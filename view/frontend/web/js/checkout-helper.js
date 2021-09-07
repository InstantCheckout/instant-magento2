define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        /**
         * @param {String} skuQtyPairs
         * @param {Boolean} confirm
         * @param {Function} callback
         * @return {String}
         */
        getCheckoutUrl: function (skuQtyPairs, confirm, callback) {
            const baseUrl = 'https://checkout.instant.one/';
            const confirmParam = 'confirm=' + confirm;
            const skuQtyPairQueryParams = _.map(skuQtyPairs, function (skuQtyPair) {
                return 'sku=' + skuQtyPair.sku + ',' + skuQtyPair.qty;
            }).join('&');

            jQuery.ajax({
                url: window.location.origin + "/instant/data/getconfig",
                type: 'GET',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    const { storeCode, appId } = data;

                    const merchantIdParam = 'merchantId=' + appId;
                    const storeCodeParam = 'storeCode=' + storeCode;

                    const url = baseUrl + 'checkout?' + confirmParam + '&' + storeCodeParam + '&' + merchantIdParam + '&' + skuQtyPairQueryParams;

                    callback(url);
                },
                error: function (jqXHR) {
                    console.log(jqXHR)
                    alert("Whoops! An error occurred during checkout.");
                }
            })
        },

        /**
         * @return {Window}
         */
        openCheckoutWindow: function (url = null) {
            const windowHeight = 800;
            const windowWidth = 490;
            const posY = window.outerHeight / 2 + window.screenY - (windowHeight / 2);
            const posX = window.outerWidth / 2 + window.screenX - (windowWidth / 2);

            return window.open(url || '', '_blank', 'location=yes,height=' + windowHeight + ',width=' + windowWidth + ',top=' + posY + ',left=' + posX + ',scrollbars=yes,status=yes');
        },

        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
        },

        /**
         * @param {String} checkoutBannerSelector
         * @param {String} checkoutButtonLoadingIndicatorSelector
         * @param {String} checkoutButtonTextSelector
         * @param {String} checkoutButtonLockIconSelector
         * @param {String} backdropSelector
         * @param {String} backToCheckoutTextElementSelector
         * 
         * Loads checkout window for entire customer cart.
         * Upon close of the window, clears the customer cart.
         */
        checkoutCustomerCart: function (
            checkoutButtonSelector,
            checkoutButtonLoadingIndicatorSelector,
            checkoutButtonTextSelector,
            checkoutButtonLockIconSelector,
            backdropSelector,
            backToCheckoutTextElementSelector) {
            const cartData = customerData.get('cart');

            if (!cartData) {
                this.showErrorAlert();
                return;
            }

            const { items } = cartData();

            const checkoutWindow = this.openCheckoutWindow();
            const skuQtyPairs = items.map(item => {
                return { sku: item.product_sku, qty: item.qty };
            })

            $(checkoutButtonSelector).attr('disabled', true);
            $(checkoutButtonLoadingIndicatorSelector).css('display', 'unset');
            $(checkoutButtonTextSelector).hide();
            $(checkoutButtonLockIconSelector).hide();
            $(backdropSelector).css('display', 'unset');
            $(backToCheckoutTextElementSelector).css('display', 'unset');
            $(backToCheckoutTextElementSelector).on('click', function () {
                checkoutWindow.focus();
            });

            this.getCheckoutUrl(skuQtyPairs, true, (url) => {
                checkoutWindow.location = url;
            });

            const loop = setInterval(function () {
                if (checkoutWindow.closed) {
                    jQuery.ajax({
                        url: window.location.origin + "/instant/cart/clear",
                        type: 'PUT',
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function () {
                            document.location.reload();
                        },
                        error: function () {
                            this.showErrorAlert();
                        }
                    })
                    clearInterval(loop);
                }
            }, 200);
        }
    };
});
