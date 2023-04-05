define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
        },

        getInstantPayParams: function () {
            if (typeof window.checkoutConfig.payment.instant == "undefined")
                return null;

            return window.checkoutConfig.payment.instant;
        },

        reloadInstantConfig: function (callback) {
            $.ajax({
                url: window.location.origin + "/instant/data/getconfig/",
                type: 'GET',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    window.Instant = {
                        appId: data.appId,
                        enableMinicartBtn: data.enableMinicartBtn,
                        cartId: data.cartId,
                        enableSandbox: data.enableSandbox,
                        storeCode: data.storeCode,
                        mcBtnWidth: data.mcBtnWidth,
                        cpageBtnWidth: data.cpageBtnWidth,
                        shouldResizeCartIndexBtn: data.shouldResizeCartIndexBtn,
                        shouldResizePdpBtn: data.shouldResizePdpBtn,
                        disabledForCustomerGroup: data.disabledForCustomerGroup,
                        currentCurrencyCode: data.currentCurrencyCode,
                        baseCurrencyCode: data.baseCurrencyCode,
                        swipeToBuy: data.swipeToBuy,
                        mcBtnCustomStyle: data.mcBtnCustomStyle,
                        mcBtnContainerCustomStyle: data.mcBtnContainerCustomStyle,
                        mcBtnHideOrStrike: data.mcBtnHideOrStrike,
                        cindexBtnCustomStyle: data.cindexBtnCustomStyle,
                        cindexBtnContainerCustomStyle: data.cindexBtnContainerCustomStyle,
                        cindexBtnHideOrStrike: data.cindexBtnHideOrStrike,
                        cpageBtnCustomStyle: data.cpageBtnCustomStyle,
                        cpageBtnContainerCustomStyle: data.cpageBtnContainerCustomStyle,
                        cpageBtnHideOrStrike: data.cpageBtnHideOrStrike,
                        customer: data.customer,
                        address: data.address,
                    };

                    const setSessionIdInterval = setInterval(() => {
                        if (window.InstantM2) {
                            window.InstantM2.sessionId = data.sessionId;
                            clearInterval(setSessionIdInterval);
                        }
                    }, 100)

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
            const btnStyle = $(mcBtnSelector).attr('style') || '';
            if (window.Instant.mcBtnCustomStyle && btnStyle.indexOf(window.Instant.mcBtnCustomStyle) === -1) {
                $(mcBtnSelector).attr('style', btnStyle ? btnStyle + window.Instant.mcBtnCustomStyle : window.Instant.mcBtnCustomStyle);
            }

            // Apply any custom styles to button outer container specified in config
            const containerStyle = $(mcBtnContainerSelector).attr('style') || '';
            if (window.Instant.mcBtnContainerCustomStyle && containerStyle.indexOf(window.Instant.mcBtnContainerCustomStyle) === -1) {
                $(mcBtnContainerSelector).attr('style', containerStyle ? containerStyle + window.Instant.mcBtnContainerCustomStyle : window.Instant.mcBtnContainerCustomStyle);
            }

            // Hide OR strike if specified in config
            if (window.Instant.mcBtnHideOrStrike) {
                $(mcBtnOrStrikeSelector).css('display', 'none');
            }

            const widthToSet = (window.Instant.mcBtnWidth && parseInt(window.Instant.mcBtnWidth) > 0) ? window.Instant.mcBtnWidth : "90";
            $(mcBtnWrapperSelector).css('width', widthToSet + '%');
            $(mcBtnContainerSelector).css('display', 'flex');
            $(mcBtnSelector).prop('disabled', false);
        },

        setCartIndexBtnAttributes: function (shouldResize) {
            const cartIndexBtnContainerSelector = '#ic-cindex-btn-container';
            const cartIndexBtnWrapperSelector = '#ic-cindex-btn-wrapper';
            const cartIndexBtnSelector = '#ic-cindex-btn';
            const cartIndexBtnOrStrikeSelector = '#ic-cindex-btn-strike';

            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                $(cartIndexBtnContainerSelector).css('display', 'none');
                return;
            }

            // Apply any custom styles to button specified in config
            const btnStyle = $(cartIndexBtnSelector).attr('style') || '';
            if (window.Instant.cindexBtnCustomStyle && btnStyle.indexOf(window.Instant.cindexBtnCustomStyle) === -1) {
                $(cartIndexBtnSelector).attr('style', btnStyle ? btnStyle + window.Instant.cindexBtnCustomStyle : window.Instant.cindexBtnCustomStyle);
            }

            // Apply any custom styles to button outer container specified in config
            const containerStyle = $(cartIndexBtnContainerSelector).attr('style') || '';
            if (window.Instant.cindexBtnContainerCustomStyle && containerStyle.indexOf(window.Instant.cindexBtnContainerCustomStyle) === -1) {
                $(cartIndexBtnContainerSelector).attr('style', containerStyle ? containerStyle + window.Instant.cindexBtnContainerCustomStyle : window.Instant.cindexBtnContainerCustomStyle);
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
        },

        setCheckoutPageBtnAttributes: function () {
            const checkoutPageBtnContainerSelector = '#ic-cpage-btn-container';
            const checkoutPageBtnWrapperSelector = '#ic-cpage-btn-wrapper';
            const checkoutPageBtnSelector = '#ic-cpage-btn';
            const checkoutPageBtnOrStrikeSelector = '#ic-cpage-btn-strike';

            if (this.isWindowInstant() && !this.shouldEnableInstantBtn()) {
                $(checkoutPageBtnContainerSelector).css('display', 'none');
                return;
            }

            // Apply any custom styles to button specified in config
            const btnStyle = $(checkoutPageBtnSelector).attr('style') || '';
            if (window.Instant.cpageBtnCustomStyle && btnStyle.indexOf(window.Instant.cpageBtnCustomStyle) === -1) {
                $(checkoutPageBtnSelector).attr('style', btnStyle ? btnStyle + window.Instant.cpageBtnCustomStyle : window.Instant.cpageBtnCustomStyle);
            }

            // Apply any custom styles to button outer container specified in config
            const containerStyle = $(checkoutPageBtnContainerSelector).attr('style') || '';
            if (window.Instant.cpageBtnContainerCustomStyle && btnStyle.indexOf(window.Instant.cpageBtnContainerCustomStyle) === -1) {
                $(checkoutPageBtnContainerSelector).attr('style', containerStyle ? containerStyle + window.Instant.cpageBtnContainerCustomStyle : window.Instant.cpageBtnContainerCustomStyle);
            }

            // Hide OR strike if specified in config
            if (window.Instant.cpageBtnHideOrStrike) {
                $(checkoutPageBtnOrStrikeSelector).css('display', 'none');
            }

            const widthToSet = (window.Instant.cpageBtnWidth && parseInt(window.Instant.cpageBtnWidth) > 0) ? window.Instant.cpageBtnWidth : "60";
            $(checkoutPageBtnWrapperSelector).css('width', widthToSet + '%');

            $(checkoutPageBtnContainerSelector).css('display', 'flex');
            $(checkoutPageBtnSelector).prop('disabled', false);
        },

        shouldEnableInstantBtn: function () {
            const areBaseAndCurrentCurrenciesEqual = window.Instant.baseCurrencyCode === window.Instant.currentCurrencyCode;
            return areBaseAndCurrentCurrenciesEqual && !window.Instant.disabledForCustomerGroup;
        },

        refreshInstantButtons: function () {
            this.handleInstantAwareFunc(() => {
                this.setMinicartBtnAttributes();
                this.setCartIndexBtnAttributes();
                this.setCheckoutPageBtnAttributes();
            });
        },
    };
});
