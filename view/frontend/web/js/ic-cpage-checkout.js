define([
    'uiComponent',
    'Instant_Checkout/js/ic-helper'
], function (Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        initialize: function () {
            this._super();

            const verificationElementEmailFieldSelector = checkoutHelper.getInstantPayParams().verificationElement.emailFieldSelector;
            const bannerElementTargetElementSelector = checkoutHelper.getInstantPayParams().bannerElement.targetElementSelector;
            const bannerElementShowAfterElement = checkoutHelper.getInstantPayParams().bannerElement.shouldAppendToElement;
            const bannerElementTheme = checkoutHelper.getInstantPayParams().bannerElement.theme;

            console.log('verificationElementEmailFieldSelector', verificationElementEmailFieldSelector)
            console.log('bannerElementTargetElementSelector', bannerElementTargetElementSelector)
            console.log('bannerElementShowAfterElement', bannerElementShowAfterElement)

            const verificationElementLoad = setInterval(() => {
                if (document.querySelector(verificationElementEmailFieldSelector)) {
                    window.InstantJS.createVerificationElement(
                        verificationElementEmailFieldSelector,
                        {
                            merchantId: window.Instant.appId,
                            storeCode: window.Instant.storeCode,
                            cartId: window.Instant.cartId
                        });
                    clearInterval(verificationElementLoad);
                }
            }, 10)

            const bannerElementLoad = setInterval(() => {
                if (document.querySelector(bannerElementTargetElementSelector)) {
                    window.InstantJS.createInstantPayBannerElement(
                        bannerElementTargetElementSelector,
                        window.checkoutConfig && window.checkoutConfig.customerData && window.checkoutConfig.customerData.email || '',
                        bannerElementShowAfterElement,
                        bannerElementTheme
                    );
                    clearInterval(bannerElementLoad);
                }
            }, 10)

            return this;
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },
    });
});
