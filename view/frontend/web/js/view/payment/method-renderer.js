define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'instantpay',
                component: 'Instant_Checkout/js/view/payment/method-renderer/instantpay'
            }
        );
        return Component.extend({});
    }
);