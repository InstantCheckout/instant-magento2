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

            const verificationElementLoad = setInterval(() => {
                if (window.InstantJS && document.querySelector(verificationElementEmailFieldSelector)) {
                    window.InstantJS.createVerificationElement(
                        verificationElementEmailFieldSelector,
                        {
                            merchantId: window.Instant.appId,
                            storeCode: window.Instant.storeCode,
                            cartId: window.Instant.cartId
                        });
                    clearInterval(verificationElementLoad);
                }
            }, 100);

            const bannerElementLoad = setInterval(() => {
                if (window.InstantJS && document.querySelector(bannerElementTargetElementSelector)) {
                    window.InstantJS.createInstantPayBannerElement(
                        bannerElementTargetElementSelector,
                        window.checkoutConfig?.customerData?.email ?? '',
                        window.checkoutConfig?.customerData?.firstname ?? '',
                        bannerElementShowAfterElement,
                        bannerElementTheme
                    );

                    clearInterval(bannerElementLoad);
                }
            },100);

            // Clear the intervals if we don't find either dom element after 30 seconds.
            setTimeout(() => {
                if (verificationElementLoad) {
                    clearInterval(verificationElementLoad);
                }

                if (bannerElementLoad) {
                    clearInterval(bannerElementLoad);
                }
            }, 30000)

            return this;
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },
    });
});
