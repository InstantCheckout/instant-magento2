define(
    [
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/customer-data'
    ],
    function (urlBuilder, storage, customerData) {
        'use strict';
        return function (result, callback)
        {
            var serviceUrl = urlBuilder.createUrl('/stripe/payments/confirm_payment', {});

            // This API call may inactivate the customer cart
            customerData.invalidate(['cart']);

            return storage.post(
                serviceUrl,
                JSON.stringify({ result: result })
            ).always(callback);
        };
    }
);
