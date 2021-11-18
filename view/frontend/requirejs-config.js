var config = {
    map: {
        '*': {
            "checkoutHelper": 'Instant_Checkout/js/checkout-helper',
        },
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Instant_Checkout/js/price-box-mixin': true
            }
        }
    }
};