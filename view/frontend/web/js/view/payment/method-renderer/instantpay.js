define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_CheckoutAgreements/js/model/agreement-validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'StripeIntegration_Payments/js/action/post-restore-quote',
        'Magento_Customer/js/customer-data',
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'StripeIntegration_Payments/js/action/post-update-cart',
        'StripeIntegration_Payments/js/action/post-confirm-payment'
    ],
    function (ko, Component, agreementValidator, additionalValidators, placeOrderAction, restoreQuoteAction, customerData, $, mageTranslate, quote, updateCartAction, confirmPaymentAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Instant_Checkout/payment/instantpay'
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading',
                        'permanentError',
                        'isOrderPlaced',
                    ]);

                this.isOrderPlaced(false);

                return this;
            },

            handlePlaceOrderErrors: function (result) {
                if (result && result.responseJSON && result.responseJSON.message)
                    this.showError(result.responseJSON.message);
                else {
                    this.showError($t("The order could not be placed. Please contact us for assistance."));

                    if (result && result.responseText)
                        console.error(result.responseText);
                    else
                        console.error(result);
                }
            },

            getInstantParam: function (param) {
                if (typeof window.checkoutConfig.payment.instantpay == "undefined")
                    return null;

                if (typeof window.checkoutConfig.payment.instantpay[param] == "undefined")
                    return null;

                return window.checkoutConfig.payment.instantpay[param];
            },

            onRender: function () {
                this.isLoading(true);

                const storeCode = this.getInstantParam('storeCode');
                const merchantId = this.getInstantParam('merchantId');

                if (document.getElementById('instant-payment-element') === null)
                    return this.crash("Cannot initialize Payment Element on a DOM that does not contain a div.instant-payment-element.");

                if (!window.InstantJS)
                    return this.crash("Cannot initialize Payment Element as InstantJS is not available.");

                if (!storeCode || !merchantId)
                    return this.crash("Cannot initialize Payment Element as either merchantId or storeCode is unavailable.");

                window.InstantJS.createPaymentElement('instant-payment-element', {
                    storeCode,
                    merchantId,
                }, () => {
                    this.isLoading(false);
                }, (err) => {
                    this.showError("An error occurred. Please try again later.")
                    this.isLoading(false);
                });
            },

            isPlaceOrderEnabled: function () {
                return quote.billingAddress() && quote.billingAddress().canUseForBilling();
            },

            showError: function (message) {
                this.isLoading(false);
                this.isPlaceOrderEnabled(true);
                this.messageContainer.addErrorMessage({ "message": message });
            },

            validate: function () {
                return agreementValidator.validate() && additionalValidators.validate();
            },

            getCode: function () {
                return 'instantpay';
            },

            isCollapsed: function () {
                if (this.isChecked() == this.getCode()) {
                    return false;
                }
                else {
                    return true;
                }
            },

            /**
             * @return {*}
             */
            getPlaceOrderDeferredObject: function () {
                return placeOrderAction({
                    'method': this.item.method
                }, this.messageContainer);
            },

            placeNewOrder: function () {
                var self = this;
                console.log('this.item', this.item)

                this.isLoading(false); // Needed for the terms and conditions checkbox
                this.getPlaceOrderDeferredObject()
                    .fail(this.handlePlaceOrderErrors.bind(this))
                    .done(this.onOrderPlaced.bind(this))
                    .always(function (response, status, xhr) {
                        if (status != "success") {
                            self.isLoading(false);
                            self.isPlaceOrderEnabled(true);
                        }
                    });
            },

            getStripeParam: function (param) {
                if (typeof window.checkoutConfig.payment.stripe_payments == "undefined")
                    return null;

                if (typeof window.checkoutConfig.payment.stripe_payments.initParams == "undefined")
                    return null;

                if (typeof window.checkoutConfig.payment.stripe_payments.initParams[param] == "undefined")
                    return null;

                return window.checkoutConfig.payment.stripe_payments.initParams[param];
            },

            getSelectedMethod: function (param) {
                var selection = this.selection();
                if (!selection)
                    return null;

                if (typeof selection[param] == "undefined")
                    return null;

                return selection[param];
            },

            onOrderPlaced: function (orderId, outcome, response) {
                console.log('orderId', orderId);
                console.log('outcome', outcome);
                console.log('response', response);
                console.log('inside onOrderPlaced!')
                if (!this.isOrderPlaced() && isNaN(orderId))
                    return this.softCrash("The order was placed but the response from the server did not include a numeric order ID.");
                else
                    this.isOrderPlaced(true);

                this.isLoading(true);

                var self = this;
                restoreQuoteAction(function () {
                    window.InstantJS.confirmPaymentElement(orderId, (res) => {
                        console.log("Successfully confirmed payment element", res)
                        self.isLoading(false);
                        if (res.order) {
                            customerData.invalidate(['cart']);
                            var successUrl = 'http://178.128.81.251/checkout/onepage/success/';
                        } else {
                            $.mage.redirect(successUrl);
                            self.showError(res.error.message);
                            self.restoreQuote(result);
                        }
                    }, (err) => {
                        console.log("Error confirming payment element", err)
                        self.isLoading(false);
                        self.showError(err.error.message);

                        confirmPaymentAction(err, function (result, outcome, response) {
                            console.log('confirmPaymentAction err', err);
                            var data = JSON.parse(err);
                            if (typeof data.redirect != "undefined") {
                                $.mage.redirect(data.redirect);
                                return;
                            }
                        });

                        console.error(err);
                    });
                });
            },

            placeOrder: function () {
                this.messageContainer.clear();

                if (!window.InstantJS.paymentElement.isValid()) {
                    return this.showError(window.InstantJS.paymentElement.errors.length > 0 ? window.InstantJS.paymentElement.errors[0] : 'Please complete your payment details.');
                }

                if (!this.validate())
                    return;


                this.isLoading(true);

                var placeNewOrder = this.placeNewOrder.bind(this);
                var reConfirmPayment = this.onOrderPlaced.bind(this);
                var self = this;

                if (this.isOrderPlaced()) {
                    updateCartAction(null, function (result, outcome, response) {
                        placeNewOrder();

                        // self.isLoading(false);
                        // try {
                        //     var data = JSON.parse(result);
                        //     console.log(data, 'data');
                        //     if (data.error) {
                        //         self.showError(data.error);
                        //     }
                        //     else if (data.redirect) {
                        //         $.mage.redirect(data.redirect);
                        //     }
                        //     else if (data.placeNewOrder) {
                        //         placeNewOrder();
                        //     }
                        //     else {
                        //         reConfirmPayment();
                        //     }
                        // }
                        // catch (e) {
                        //     self.showError($t("The order could not be placed. Please contact us for assistance."));
                        //     console.error(e.message);
                        // }
                    });
                }
                else {
                    try {
                        console.log('placing new order');
                        placeNewOrder();
                        // updateCartAction(null, function (result, outcome, response) {
                        // });
                    }
                    catch (e) {
                        console.log('err', e)
                        self.showError("The order could not be placed. Please contact us for assistance.");
                        console.error(e.message);
                    }
                }

                return false;
            },

        });
    }
);