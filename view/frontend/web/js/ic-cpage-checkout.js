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

            const verificationElementEnabled = checkoutHelper.getInstantPayParams().bannerElement.theme;
            const verificationElementEmailFieldSelector = checkoutHelper.getInstantPayParams().verificationElement.emailFieldSelector;
            
            const bannerElementEnabled = checkoutHelper.getInstantPayParams().bannerElement.theme;
            const bannerElementTargetElementSelector = checkoutHelper.getInstantPayParams().bannerElement.targetElementSelector;
            const bannerElementShowAfterElement = checkoutHelper.getInstantPayParams().bannerElement.shouldAppendToElement;
            const bannerElementTheme = checkoutHelper.getInstantPayParams().bannerElement.theme;

            let verificationElementLoadInterval;
            let bannerElementLoadInterval;
            if (verificationElementEnabled){
                const verificationElementLoadInterval = setInterval(() => {
                    if (window.InstantJS && (document.querySelector(verificationElementEmailFieldSelector) || window.checkoutConfig?.customerData?.email)) {
                        clearInterval(verificationElementLoadInterval);
    
                        window.InstantJS.createVerificationElement(
                            verificationElementEmailFieldSelector,
                            {
                                merchantId: window.Instant.appId,
                                storeCode: window.Instant.storeCode,
                                cartId: window.Instant.cartId
                            }
                        );
                    }
                }, 50);
            }

            if (bannerElementEnabled){
                const bannerElementLoadInterval = setInterval(() => {
                    if (window.InstantJS && document.querySelector(bannerElementTargetElementSelector)) {
                        clearInterval(bannerElementLoadInterval);
    
                        window.InstantJS.createInstantPayBannerElement(
                            bannerElementTargetElementSelector,
                            window.checkoutConfig?.customerData?.email ?? '',
                            window.checkoutConfig?.customerData?.firstname ?? '',
                            bannerElementShowAfterElement,
                            bannerElementTheme
                        );
                    }
                }, 50);
            }

            // Clear the intervals if we don't find either dom element after 30 seconds.
            setTimeout(() => {
                if (verificationElementLoadInterval) {
                    clearInterval(verificationElementLoadInterval);
                }
                if (bannerElementLoadInterval) {
                    clearInterval(bannerElementLoadInterval);
                }
            }, 30000)

            return this;
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },
    });
});
