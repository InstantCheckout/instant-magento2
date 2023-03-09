define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_CheckoutAgreements/js/model/agreement-validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/customer-data',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Instant_Checkout/js/action/post-handle-failed-payment',
        'Instant_Checkout/js/ic-helper'
    ],
    function (Component, agreementValidator, additionalValidators, placeOrderAction, customerData, $, quote, handlePaymentFailedAction, instantHelper) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Instant_Checkout/payment/instant'
            },

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
                return instantHelper.getInstantPayParams().enabled;
            },

            crash: function (message) {
                this.isLoading(false);
                this.permanentError(msg);
                console.error("Instant Pay Error: " + message);
            },

            onRender: function () {
                const storeCode = instantHelper.getInstantPayParams().storeCode;
                const merchantId = instantHelper.getInstantPayParams().merchantId;

                if (document.getElementById('instant-payment-element') === null)
                    return this.crash("Cannot initialize Payment Element on a DOM that does not contain a div.instant-payment-element.");

                if (!window.InstantJS)
                    return this.crash("Cannot initialize Payment Element as InstantJS is not available.");

                if (!storeCode || !merchantId)
                    return this.crash("Cannot initialize Payment Element as either merchantId or storeCode is unavailable.");

                window.InstantJS.createPaymentElement('instant-payment-element', {
                    storeCode,
                    merchantId,
                    userEmail: window.checkoutConfig.customerData.email,
                }, () => {
                    // Instant Payment Element loaded
                }, (err) => {
                    this.showError("An error occurred. Please try again later.")
                    this.isLoading(false);
                });
            },

            isPlaceOrderEnabled: function () {
                return quote.billingAddress() && quote.billingAddress().canUseForBilling();
            },

            placeOrder: function () {
                this.messageContainer.clear();

                if (!window.InstantJS.paymentElement.isValid()) {
                    return this.showError(window.InstantJS.paymentElement.errors.length > 0 ? window.InstantJS.paymentElement.errors[0] : 'Please complete your payment details.');
                }
                if (!(agreementValidator.validate() && additionalValidators.validate()))
                    return;

                this.isLoading(true);

                var self = this;

                this.isLoading(false); // Needed for the terms and conditions checkbox

                placeOrderAction({
                    'method': this.item.method
                }, this.messageContainer)
                    .fail(function (result) {
                        if (result && result.responseJSON && result.responseJSON.message)
                            self.showError(result.responseJSON.message);
                        else {
                            self.crash("The order could not be placed. Please contact us for assistance.");

                            if (result && result.responseText)
                                console.error(result.responseText);
                            else
                                console.error(result);
                        }
                    })
                    .done(function (orderId) {
                        if (isNaN(orderId))
                            return self.crash("The order was placed but the response from the server did not include a numeric order ID.", true);

                        self.isLoading(true);

                        window.InstantJS.confirmPaymentElement(orderId, (res) => {
                            self.isLoading(false);
                            customerData.invalidate(['cart']);
                            $.mage.redirect(instantHelper.getInstantPayParams().successUrl);

                        }, (err) => {
                            handlePaymentFailedAction(function () {
                                self.isLoading(false);
                                self.showError(err.message);
                            });
                        });
                    })
                    .always(function (response, status, xhr) {
                        if (status != "success") {
                            self.isLoading(false);
                            self.isPlaceOrderEnabled(true);
                        }
                    });

                return false;
            },
        });
    }
);