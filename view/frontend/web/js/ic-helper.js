define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
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
            const heightToSet = height || (this.isWindowInstant() && window.Instant.btnHeight ? window.Instant.btnHeight : '45');
            const borderRadiusToSet = borderRadius || (this.isWindowInstant() && window.Instant.btnBorderRadius ? window.Instant.btnBorderRadius : '3')
            const backgroundToSet = backgroundColor || (this.isWindowInstant() && window.Instant.btnColor ? window.Instant.btnColor : '#00D160');

            $(buttonSelector).css('min-height', heightToSet + 'px');
            $(buttonSelector).css('border-radius', borderRadiusToSet + 'px');
            $(buttonSelector).css('background', backgroundToSet);
        },

        configurePdpBtn: function (shouldResizePdpBtn, height, borderRadius) {
            const pdpBtnContainerSelector = '#ic-pdp-btn-container';
            const pdpBtnSelector = '#ic-pdp-btn';
            const atcBtnSelector = '#product-addtocart-button';

            const resizePdpBtn = this.isWindowInstant() ? window.Instant.shouldResizePdpBtn : shouldResizePdpBtn;

            // If we should resize pdp button
            // We resize it to the size of the add to cart button
            if (resizePdpBtn) {
                $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                $(window).resize(function () {
                    $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                });
            }

            this.setBtnAttributes(pdpBtnSelector, height, borderRadius);
        },

        setMinicartBtnAttributes: function () {
            const mcBtnContainerSelector = '#ic-mc-btn-container';
            const mcBtnWrapperSelector = '#ic-mc-btn-wrapper';
            const mcBtnSelector = '#ic-mc-btn';
            const mcBtnOrStrikeSelector = '#ic-mc-btn-strike';

            const cartData = this.getCustomerCartData();
            if (!(cartData && cartData.items && cartData.items.length > 0 && window.Instant.enableMinicartBtn && this.shouldEnableInstantBtn())) {
                $(mcBtnContainerSelector).css('display', 'none');
                return;
            }

            // Apply any custom styles to button specified in config
            if (window.Instant.mcBtnCustomStyle) {
                const btnStyle = $(mcBtnSelector).attr('style');
                $(mcBtnSelector).attr('style', btnStyle ? btnStyle : '' + window.Instant.mcBtnCustomStyle);
            }

            // Apply any custom styles to button outer container specified in config
            if (window.Instant.mcBtnContainerCustomStyle) {
                const containerStyle = $(mcBtnContainerSelector).attr('style');
                $(mcBtnContainerSelector).attr('style', containerStyle ? containerStyle : '' + window.Instant.mcBtnContainerCustomStyle);
            }

            // Hide OR strike if specified in config
            if (window.Instant.mcBtnHideOrStrike) {
                $(mcBtnOrStrikeSelector).css('display', 'none');
            }

            const widthToSet = (window.Instant.mcBtnWidth && parseInt(window.Instant.mcBtnWidth) > 0) ? window.Instant.mcBtnWidth : "90";
            $(mcBtnWrapperSelector).css('width', widthToSet + '%');
            $(mcBtnContainerSelector).css('display', 'flex');
            $(mcBtnSelector).prop('disabled', false);
            this.setBtnAttributes(mcBtnSelector);
        },

        setCartIndexBtnAttributes: function (shouldResize, height, borderRadius, btnColor) {
            const cartIndexBtnContainerSelector = '#ic-cindex-btn-container';
            const cartIndexBtnWrapperSelector = '#ic-cindex-btn-wrapper';
            const cartIndexBtnSelector = '#ic-cindex-btn';
            const cartIndexBtnOrStrikeSelector = '#ic-cindex-btn-strike';

            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                $(cartIndexBtnContainerSelector).css('display', 'none');
                return;
            }

            // Apply any custom styles to button specified in config
            if (window.Instant.cindexBtnCustomStyle) {
                const btnStyle = $(cartIndexBtnSelector).attr('style');
                $(cartIndexBtnSelector).attr('style', btnStyle ? btnStyle : '' + window.Instant.cindexBtnCustomStyle);
            }

            // Apply any custom styles to button outer container specified in config
            if (window.Instant.cindexBtnContainerCustomStyle) {
                const containerStyle = $(cartIndexBtnContainerSelector).attr('style');
                $(cartIndexBtnContainerSelector).attr('style', containerStyle ? containerStyle : '' + window.Instant.cindexBtnContainerCustomStyle);
            }

            // Hide OR strike if specified in config
            if (window.Instant.cindexBtnHideOrStrike) {
                $(cartIndexBtnOrStrikeSelector).css('display', 'none');
            }

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
            const checkoutPageBtnContainerSelector = '#ic-cpage-btn-container';
            const checkoutPageBtnWrapperSelector = '#ic-cpage-btn-wrapper';
            const checkoutPageBtnSelector = '#ic-cpage-btn';

            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                $(checkoutPageBtnContainerSelector).css('display', 'none');
                return;
            }

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

            return !cartContainsBlacklistedSku && areBaseAndCurrentCurrenciesEqual && !window.Instant.disabledForCustomerGroup;
        },

        refreshInstantButtons: function () {
            this.handleInstantAwareFunc(() => {
                this.configurePdpBtn();
                this.setMinicartBtnAttributes();
                this.setCartIndexBtnAttributes();
                this.setCheckoutPageBtnAttributes();
            });
        },
    };
});
