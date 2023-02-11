define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/customer-data'
    ],
    function (quote, urlBuilder, storage, customerData) {
        'use strict';
        return function ()
        {
            var serviceUrl = urlBuilder.createUrl('/stripe/payments/update_subscription', {});

            // This API call may inactivate the customer cart
            customerData.invalidate(['cart']);

            var payload = {
                billingAddress: quote.billingAddress()
            };

            if (quote.shippingAddress())
                payload.shippingAddress = quote.shippingAddress();

            if (quote.shippingMethod())
                payload.shippingMethod = quote.shippingMethod();

            var totals = quote.totals();
            if (typeof totals.coupon_code != "undefined" && totals.coupon_code && totals.coupon_code.length > 0)
                payload.couponCode = totals.coupon_code;

            return storage.post(serviceUrl, JSON.stringify(payload));
        };
    }
);
