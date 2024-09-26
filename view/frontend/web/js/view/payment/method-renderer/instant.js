define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Instant_Checkout/payment/instant'
            },
            // 
            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading',
                        'permanentError',
                    ]);

                return this;
            },

            showError: function (message) {
                this.isLoading(false);
                this.messageContainer.addErrorMessage({ "message": message });
            },

            isEnabled: function () {
                return window.Instant.enableCheckoutPage;
            },

            crash: function (message) {
                this.isLoading(false);
                this.permanentError(msg);
                console.error("Instant Pay Error: " + message);
            },

            onRender: function () {
            },

            isPlaceOrderEnabled: function () {
                return true;
            },

            consolidateArrayExceptLastElement: function (inputArray) {
                if (inputArray.length <= 1) {
                    // If there's only one element or none, return the array as it is or with an empty string added
                    return inputArray.length === 1 ? [inputArray[0], ''] : ['', ''];
                }
                // Use slice to get all elements except the last and join them into a single string with a space between each
                const consolidatedFirstPart = inputArray.slice(0, -1).join(' ');
                // Get the last element of the array
                const lastElement = inputArray[inputArray.length - 1];
                // Return the new array with two elements as specified
                return [consolidatedFirstPart, lastElement];
            },


            placeOrder: function () {
                const shippingAddress = quote.shippingAddress();
                const addressLines = this.consolidateArrayExceptLastElement(shippingAddress.street);

                const address = {
                    address1: addressLines[0],
                    address2: addressLines[1],
                    city: shippingAddress.city,
                    regionCode: shippingAddress.regionCode,
                    postCode: shippingAddress.postcode,
                    countryCode: shippingAddress.countryId,
                };

                const customer = {
                    firstName: shippingAddress.firstname,
                    lastName: shippingAddress.lastname,
                    phone: shippingAddress.telephone,
                    email: quote.guestEmail,
                };

                window.InstantM2.handleGenericCheckoutPaymentSectionButtonClicked(customer, address);
            },
        });
    }
);