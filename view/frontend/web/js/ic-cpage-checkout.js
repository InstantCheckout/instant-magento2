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
            return this;
        },

        render: function () {
            checkoutHelper.refreshInstantButtons();
        },
    });
});
