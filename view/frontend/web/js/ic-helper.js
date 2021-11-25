define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
        },

        getCustomerCartData: function () {
            const customerDataCart = customerData.get('cart');
            if (!customerDataCart) {
                this.showErrorAlert();
                return;
            }

            const cartData = customerDataCart();
            if (!cartData) {
                this.showErrorAlert();
                return;
            }

            return cartData;
        },

        handleCartTotalChanged: function () {
            this.getConfig((data) => {
                window.Instant['config'] = data;

                const { enableMinicartBtn, isGuest, disabledForSkusContaining } = data;
                let { disabledTotalThreshold } = data;

                const cartData = this.getCustomerCartData();

                let cartContainsBlacklistedSku = false;
                cartData.items.forEach(item => {
                    disabledForSkusContaining.forEach(x => {
                        if (x && item.product_sku.indexOf(x) !== -1) {
                            cartContainsBlacklistedSku = true;
                        }
                    })
                })

                const shouldEnableInstantBtn = !cartContainsBlacklistedSku && !(disabledTotalThreshold && parseFloat(disabledTotalThreshold) > 0 && parseFloat(cartData.subtotalAmount) > disabledTotalThreshold) && isGuest;
                const shouldEnableMinicartInstantBtn = cartData && cartData.items && cartData.items.length > 0 && enableMinicartBtn && shouldEnableInstantBtn;

                $('#ic-mc-btn-container').css('display', shouldEnableMinicartInstantBtn ? 'flex' : 'none');
                $('#ic-cindex-btn-container').css('display', shouldEnableInstantBtn ? 'flex' : 'none');
                $('#ic-cpage-btn-container').css('display', shouldEnableInstantBtn ? 'flex' : 'none');
            })
        },

        getConfig: function (onSuccess) {
            jQuery.ajax({
                url: window.location.origin + "/instant/data/getconfig",
                type: 'GET',
                cache: false,
                retryLimit: 3,
                contentType: false,
                processData: false,
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
        },

        checkoutCustomerCart: function (
            checkoutButtonSelector,
            checkoutButtonLoadingIndicatorSelector,
            checkoutButtonTextSelector,
            checkoutButtonLockIconSelector,
            desktopBackdropSelector,
            mobileBackdropSelector,
            desktopBackToCheckoutTextElementSelector,
            mobileBackToShoppingSelector,
            sourceLocation) {
            let checkoutWindow;
            if (window.Instant.canSetWindowLocation()) {
                checkoutWindow = window.Instant.init();

                if (window.Instant.isClientMobileOrTablet()) {
                    $(mobileBackdropSelector).css('display', 'unset');
                    $(mobileBackToShoppingSelector).css('display', 'unset');
                    $(mobileBackToShoppingSelector).on('click', function () {
                        $(mobileBackdropSelector).css('display', 'none');
                        $(checkoutButtonSelector).attr('disabled', false);
                        $(checkoutButtonTextSelector).show();
                        $(checkoutButtonLockIconSelector).show();
                        $(checkoutButtonLoadingIndicatorSelector).css('display', 'none');
                    });
                } else {
                    $(desktopBackdropSelector).css('display', 'unset');
                    $(desktopBackToCheckoutTextElementSelector).css('display', 'unset');
                    $(desktopBackToCheckoutTextElementSelector).on('click', function () {
                        checkoutWindow.focus();
                    });
                }
            }

            $(checkoutButtonSelector).attr('disabled', true);
            $(checkoutButtonLoadingIndicatorSelector).css('display', 'unset');
            $(checkoutButtonTextSelector).hide();
            $(checkoutButtonLockIconSelector).hide();

            const url = window.Instant.getCheckoutUrl(null, window.Instant.config.checkoutConfig.quoteData.entity_id, sourceLocation);
            if (!url) {
                this.showErrorAlert();
                return;
            }

            if (checkoutWindow) {
                checkoutWindow.location = url;
            } else {
                window.location = url;
            }

            if (checkoutWindow) {
                const loop = setInterval(function () {
                    if (checkoutWindow.closed) {
                        $(checkoutButtonSelector).attr('disabled', false);
                        $(checkoutButtonTextSelector).show();
                        $(checkoutButtonLoadingIndicatorSelector).css('display', 'none');
                        $(checkoutButtonLockIconSelector).show();
                        $(mobileBackdropSelector).css('display', 'none');
                        $(desktopBackdropSelector).css('display', 'none');

                        clearInterval(loop);
                    }
                }, 500);
            }

        }
    };
});
