define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
        },

        getInstantBaseUrl: function () {
            const isSandbox = window.Instant.enableSandbox;
            return 'https://' + (isSandbox ? 'staging.' : '') + 'checkout.instant.one/';
        },

        reloadInstantConfig: function (callback) {
            $.ajax({
                url: window.location.origin + "/instant/data/getconfig",
                type: 'GET',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    window.Instant = data;

                    $(document).trigger('instant-config-loaded');
                    if (typeof callback === 'function') {
                        callback();
                    }
                },
            })
        },

        showBackdrop: function (checkoutWindow) {
            const onClick = () => {
                if (checkoutWindow) {
                    checkoutWindow.focus();
                } else {
                    $('#ic-backdrop-container').css('display', 'none');
                }
            };

            $('#ic-backdrop-container').css('display', 'flex');
            $('.ic-backdrop').css('display', 'flex');
            $('.ic-backdrop-close').on('click', function () {
                $('#ic-backdrop-container').css('display', 'none');
            });
            $('.ic-backdrop-message').on('click', function () {
                onClick();
            });
            $('.ic-backdrop-continue').on('click', function () {
                onClick();
            });
        },

        hideBackdrop: function () {
            $('#ic-backdrop-container').css('display', 'none');
        },

        isWindowInstant: function () {
            return !!window.Instant;
        },

        handleInstantAwareFunc: function (func) {
            if (this.isWindowInstant()) {
                func();
            } else {
                this.reloadInstantConfig(func);
            }
        },

        getCheckoutUrl: function (items, cartId, source) {
            const merchantIdParam = 'merchantId=' + window.Instant.appId;
            const storeCodeParam = 'storeCode=' + window.Instant.storeCode;
            const sessionIdParam = window.Instant.sessId ? 'sessionId=' + window.Instant.sessId : '';
            const srcParam = "src=" + source;
            const confirmParam = "confirm=true";
            const currencyCodeParam = "currencyCode=" + window.Instant.currentCurrencyCode;

            var url = this.getInstantBaseUrl() + '?' + confirmParam + '&' + storeCodeParam + '&' + merchantIdParam + '&' + sessionIdParam + '&' + srcParam + '&' + currencyCodeParam;

            if (items) {
                url = url + '&' + "items=" + encodeURIComponent(JSON.stringify(items));
            }
            if (cartId) {
                url = url + '&' + "cartId=" + cartId;
            }

            return url;
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

        setBtnAttributes: function (buttonSelector, height, borderRadius, backgroundColor) {
            const heightToSet = this.isWindowInstant() ? (window.Instant.btnHeight && parseInt(window.Instant.btnHeight) >= 40 && parseInt(window.Instant.btnHeight) <= 50) ? window.Instant.btnHeight : "45" : height;
            const borderRadiusToSet = this.isWindowInstant() ? (window.Instant.btnBorderRadius && parseInt(window.Instant.btnBorderRadius) >= 0 && parseInt(window.Instant.btnBorderRadius) <= 10) ? window.Instant.btnBorderRadius : "3" : borderRadius;
            const backgroundToSet = this.isWindowInstant() ? window.Instant.btnColor : (backgroundColor ? backgroundColor : '#00D160');

            $(buttonSelector).css('height', heightToSet + 'px');
            $(buttonSelector).css('border-radius', borderRadiusToSet + 'px');
            $(buttonSelector).css('background', backgroundToSet);
        },

        configurePdpBtn: function (shouldResizePdpBtn, height, borderRadius, shouldPositionPdpBelowAtc) {
            const pdpBtnContainerSelector = '#ic-pdp-btn-container';
            const pdpBtnSelector = '#ic-pdp-btn';
            const atcBtnSelector = '#product-addtocart-button';
            const pdpBtnOrStrike = '#ic-pdp-btn-strike'

            const resizePdpBtn = this.isWindowInstant() ? window.Instant.shouldResizePdpBtn : shouldResizePdpBtn;
            const positionPdpBelowAtc = this.isWindowInstant() ? window.Instant.shouldPositionPdpBelowAtc : shouldPositionPdpBelowAtc;

            // If we should resize pdp button
            // We resize it to the size of the add to cart button
            if (resizePdpBtn) {
                $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                $(window).resize(function () {
                    $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                });
            }

            // If we should position pdp below atc, then do not attempt tp prepend pdp btn above actions
            if (positionPdpBelowAtc) {
                $(pdpBtnOrStrike).insertBefore(pdpBtnSelector);
                $(pdpBtnSelector).css('margin-bottom', '0px');
                $(pdpBtnSelector).css('margin-top', '5px');
            } else {
                $(pdpBtnContainerSelector).prependTo($(".box-tocart .fieldset .actions").first());
            }

            this.setBtnAttributes(pdpBtnSelector, height, borderRadius);
        },

        setMinicartBtnAttributes: function () {
            const cartData = this.getCustomerCartData();
            if (!(cartData && cartData.items && cartData.items.length > 0 && window.Instant.enableMinicartBtn && this.shouldEnableInstantBtn())) {
                return;
            }

            const mcBtnContainerSelector = '#ic-mc-btn-container';
            const mcBtnWrapperSelector = '#ic-mc-btn-wrapper';
            const mcBtnSelector = '#ic-mc-btn';

            const widthToSet = (window.Instant.mcBtnWidth && parseInt(window.Instant.mcBtnWidth) > 0) ? window.Instant.mcBtnWidth : "90";
            $(mcBtnWrapperSelector).css('width', widthToSet + '%');
            $(mcBtnContainerSelector).css('display', 'flex');
            $(mcBtnSelector).prop('disabled', false);
            this.setBtnAttributes(mcBtnSelector);
        },

        setCartIndexBtnAttributes: function (shouldResize, height, borderRadius, btnColor) {
            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                return;
            }

            const cartIndexBtnContainerSelector = '#ic-cindex-btn-container';
            const cartIndexBtnWrapperSelector = '#ic-cindex-btn-wrapper';
            const cartIndexBtnSelector = '#ic-cindex-btn';

            const resizeBtn = this.isWindowInstant() ? window.Instant.shouldResizeCartIndexBtn : shouldResize;
            if (resizeBtn) {
                const primaryCheckoutBtnSelector = $("button.action.primary.checkout");
                let cartIndexJqueryEl = primaryCheckoutBtnSelector;

                if (primaryCheckoutBtnSelector.length > 1) {
                    cartIndexJqueryEl = primaryCheckoutBtnSelector.eq(1);
                }

                $(cartIndexBtnWrapperSelector).css('width', cartIndexJqueryEl.outerWidth() + 'px');
                $(window).resize(function () {
                    $(cartIndexBtnWrapperSelector).css('width', cartIndexJqueryEl.outerWidth() + 'px');
                });
            }

            $(cartIndexBtnSelector).prop('disabled', false);
            $(cartIndexBtnContainerSelector).css('display', 'flex');
            this.setBtnAttributes(cartIndexBtnSelector, height, borderRadius, btnColor);
        },

        setCheckoutPageBtnAttributes: function () {
            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                return;
            }

            const checkoutPageBtnContainerSelector = '#ic-cpage-btn-container';
            const checkoutPageBtnWrapperSelector = '#ic-cpage-btn-wrapper';
            const checkoutPageBtnSelector = '#ic-cpage-btn';

            const widthToSet = (window.Instant.cpageBtnWidth && parseInt(window.Instant.cpageBtnWidth) > 0) ? window.Instant.cpageBtnWidth : "60";
            $(checkoutPageBtnWrapperSelector).css('width', widthToSet + '%');

            $(checkoutPageBtnContainerSelector).css('display', 'flex');
            $(checkoutPageBtnSelector).prop('disabled', false);
            $(checkoutPageBtnSelector).css('background', window.Instant.btnColor ? window.Instant.btnColor : '#00D160');
            this.setBtnAttributes(checkoutPageBtnSelector);
        },

        shouldEnableInstantBtn: function () {
            let cartContainsBlacklistedSku = false;
            const areBaseAndCurrentCurrenciesEqual = window.Instant.baseCurrencyCode === window.Instant.currentCurrencyCode;

            const cartData = this.getCustomerCartData();
            if (cartData && cartData.items) {
                cartData.items.forEach(item => {
                    window.Instant.disabledForSkusContaining.forEach(x => {
                        if (x && item.product_sku.indexOf(x) !== -1) {
                            cartContainsBlacklistedSku = true;
                        }
                    })
                })
            }

            return !cartContainsBlacklistedSku && areBaseAndCurrentCurrenciesEqual;
        },

        refreshInstantButtons: function () {
            this.handleInstantAwareFunc(() => {
                this.configurePdpBtn();
                this.setMinicartBtnAttributes();
                this.setCartIndexBtnAttributes();
                this.setCheckoutPageBtnAttributes();
            });
        },

        openCheckoutWindow: function (url) {
            const windowHeight = 800;
            const windowWidth = 490;
            const posY = window.outerHeight / 2 + window.screenY - (windowHeight / 2);
            const posX = window.outerWidth / 2 + window.screenX - (windowWidth / 2);

            const checkoutWindow = window.open(url, '', 'location=yes,height=' + windowHeight + ',width=' + windowWidth + ',top=' + posY + ',left=' + posX + ',scrollbars=yes,status=yes');

            this.showBackdrop(checkoutWindow);
            const loop = setInterval(function () {
                if (checkoutWindow.closed) {
                    $('#ic-backdrop-container').css('display', 'none');
                    clearInterval(loop);
                }
            }, 500);
        },

        checkoutCustomerCart: function (srcLocation) {
            if (!this.isWindowInstant()) {
                this.showErrorAlert();
                return;
            }

            this.openCheckoutWindow(this.getCheckoutUrl(null, window.Instant.cartId, srcLocation));
        },

        checkoutProduct: function (sku, qty, options = []) {
            if (!this.isWindowInstant()) {
                this.showErrorAlert();
                return;
            }

            this.openCheckoutWindow(this.getCheckoutUrl([{ sku, qty, options }], null, "pdp"));
        }
    };
});
