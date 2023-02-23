define(
    [
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/customer-data'
    ],
    function (urlBuilder, storage, customerData) {
        'use strict';
        return function (callback) {
            var serviceUrl = urlBuilder.createUrl('/instant/handle_failed_payment', {});

            // This API call may inactivate the customer cart
            customerData.invalidate(['cart']);

            return storage.post(serviceUrl).always(callback);
        };
    }
);
