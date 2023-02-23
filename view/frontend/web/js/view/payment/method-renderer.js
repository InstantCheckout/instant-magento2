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
                type: 'instant',
                component: 'Instant_Checkout/js/view/payment/method-renderer/instant'
            }
        );
        return Component.extend({});
    }
);