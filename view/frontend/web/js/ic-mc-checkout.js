define([
    'ko',
    'jquery',
    'uiComponent'
], function (ko, $, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Instant_Checkout/ic-mc-btn',
        },

        initialize: function () {
            this._super();
            return this;
        }
    });
});