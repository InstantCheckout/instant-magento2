define([
    'uiComponent',
    'Instant_Checkout/js/ic-helper'
], function (Component, checkoutHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-cpage-btn'
        },

        render: function () {
            console.log("RENDER")
            checkoutHelper.refreshInstantButtons();
        },
    });
});
