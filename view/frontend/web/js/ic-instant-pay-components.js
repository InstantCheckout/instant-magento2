define([
    'uiComponent',
    'Instant_Checkout/js/ic-helper'
], function (Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();

            const instantPayEnabled = checkoutHelper.getInstantPayParams().enabled;

            const verificationElementEmailFieldSelector = checkoutHelper.getInstantPayParams().verificationElement.emailFieldSelector;
            const bannerElementTargetElementSelector = checkoutHelper.getInstantPayParams().bannerElement.targetElementSelector;
            const bannerElementShowAfterElement = checkoutHelper.getInstantPayParams().bannerElement.shouldAppendToElement;
            const bannerElementTheme = checkoutHelper.getInstantPayParams().bannerElement.theme;

            if (instantPayEnabled) {
                const verificationElementLoadInterval = setInterval(() => {
                    if (window.InstantJS && (document.querySelector(verificationElementEmailFieldSelector) || window.checkoutConfig?.customerData?.email)) {
                        clearInterval(verificationElementLoadInterval);

                        window.InstantJS.createVerificationElement(
                            verificationElementEmailFieldSelector,
                            {
                                merchantId: checkoutHelper.getInstantPayParams().merchantId,
                                storeCode: checkoutHelper.getInstantPayParams().storeCode,
                                cartId: checkoutHelper.getInstantPayParams().cartId
                            }
                        );
                    }
                }, 10);
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
                }, 10);

                // Clear the intervals if we don't find either dom element after 30 seconds.
                setTimeout(() => {
                    if (verificationElementLoadInterval) {
                        clearInterval(verificationElementLoadInterval);
                    }
                    if (bannerElementLoadInterval) {
                        clearInterval(bannerElementLoadInterval);
                    }
                }, 30000)
            }

            return this;
        }
    });
});
