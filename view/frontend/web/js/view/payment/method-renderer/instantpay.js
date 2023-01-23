define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_CheckoutAgreements/js/model/agreement-validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'StripeIntegration_Payments/js/action/post-restore-quote',
        'Magento_Customer/js/customer-data',
        'jquery'
    ],
    function (Component, agreementValidator, additionalValidators, placeOrderAction, restoreQuoteAction, customerData, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Instant_Checkout/payment/instantpay'
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'paymentElement',
                        'isPaymentFormComplete',
                        'isPaymentFormVisible',
                        'isLoading',
                        'stripePaymentsError',
                        'permanentError',
                        'isOrderPlaced',
                        'isInitializing',
                        'isInitialized',
                        'useQuoteBillingAddress',

                        // Saved payment methods dropdown
                        'dropdownOptions',
                        'selection',
                        'isDropdownOpen'
                    ]);

                var self = this;

                // this.initParams = window.checkoutConfig.payment.stripe_payments.initParams;
                // this.isPaymentFormVisible(false);
                this.isOrderPlaced(false);
                // this.isInitializing(true);
                // this.isInitialized(false);
                // this.useQuoteBillingAddress(false);
                // this.collectCvc = ko.computed(this.shouldCollectCvc.bind(this));
                // this.isAmex = ko.computed(this.isAmexSelected.bind(this));
                // this.cardCvcElement = null;

                // trialingSubscriptions().refresh(quote); // This should be initially retrieved via a UIConfig

                // var currentTotals = quote.totals();
                // var currentShippingAddress = quote.shippingAddress();
                // var currentBillingAddress = quote.billingAddress();

                // quote.totals.subscribe(function (totals) {
                //     if (JSON.stringify(totals.total_segments) == JSON.stringify(currentTotals.total_segments))
                //         return;

                //     currentTotals = totals;

                //     trialingSubscriptions().refresh(quote);
                //     self.onQuoteTotalsChanged.bind(self)();
                //     self.isOrderPlaced(false);
                // }, this);

                // quote.paymentMethod.subscribe(function (method) {
                //     if (method.method == this.getCode() && !this.isInitializing()) {
                //         // We intentionally re-create the element because its container element may have changed
                //         var params = window.checkoutConfig.payment.stripe_payments.initParams;
                //         this.initPaymentForm(params);
                //     }
                // }, this);

                // quote.billingAddress.subscribe(function (address) {
                //     if (address && self.paymentElement && self.paymentElement.update && !self.isPaymentFormComplete()) {
                //         // Remove the postcode & country fields if a billing address has been specified
                //         var params = window.checkoutConfig.payment.stripe_payments.initParams;
                //         self.paymentElement.update(self.getPaymentElementUpdateOptions(params));
                //     }
                // });

                return this;
            },

            /**
             * @return {*}
             */
            getPlaceOrderDeferredObject: function () {
                return placeOrderAction(this.getData(), this.messageContainer);
            },

            getData: function () {
                var data = {
                    'method': this.item.method,
                    'additional_data': {
                        'client_side_confirmation': true,
                        'payment_method': this.getPaymentMethodId()
                    }
                };

                return data;
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

            onConfirm: function (result) {
                this.isLoading(false);
                if (result && result.error) {
                    this.showError(result.error.message);
                    this.restoreQuote(result);
                }
                else {
                    customerData.invalidate(['cart']);
                    var successUrl = 'http://178.128.81.251/checkout/onepage/success/';
                    $.mage.redirect(successUrl);
                }
            },

            onFail: function (result) {
                this.isLoading(false);
                this.showError("Could not confirm the payment. Please try again.");
                this.restoreQuote(result);
                console.error(result);
            },

            onOrderPlaced: function (result, outcome, response) {
                if (!this.isOrderPlaced() && isNaN(result))
                    return this.softCrash("The order was placed but the response from the server did not include a numeric order ID.");
                else
                    this.isOrderPlaced(true);

                this.isLoading(true);
                var onConfirm = this.onConfirm.bind(this);
                var onFail = this.onFail.bind(this);

                onConfirm();

                // // Non-card based confirms may redirect the customer externally. We restore the quote just before it in case the
                // // customer clicks the back button on the browser before authenticating the payment.
                // var self = this;
                // restoreQuoteAction(function () {
                //     // If we are confirming the payment with a saved method, we need a client secret and a payment method ID
                //     var selectedMethod = self.getSelectedMethod("type");

                //     var clientSecret = self.getStripeParam("clientSecret");
                //     if (!clientSecret)
                //         return self.softCrash("To confirm the payment, a client secret is necessary, but we don't have one.");

                //     var isSetup = false;
                //     if (clientSecret.indexOf("seti_") === 0)
                //         isSetup = true;

                //     var confirmParams = {
                //         payment_method: self.getSelectedMethod("value"),
                //         return_url: self.getStripeParam("successUrl")
                //     };

                //     var dropDownSelection = self.selection();
                //     if (dropDownSelection && dropDownSelection.type == "card" && dropDownSelection.cvc == 1 && !isSetup) {
                //         confirmParams.payment_method_options = {
                //             card: {
                //                 cvc: self.cardCvcElement
                //             }
                //         };
                //     }

                //     self.confirm.bind(self)(selectedMethod, confirmParams, clientSecret, isSetup, onConfirm, onFail);
                // });
            },

            showError: function (message) {
                this.isLoading(false);
                this.isPlaceOrderEnabled(true);
                this.messageContainer.addErrorMessage({ "message": message });
            },

            isPaymentFormComplete: function () {
                return true;
            },

            validate: function (elm) {
                return agreementValidator.validate() && additionalValidators.validate();
            },


            getPaymentMethodId: function () {
                var selection = this.selection();

                if (selection && typeof selection.value != "undefined" && selection.value != "new")
                    return selection.value;

                return null;
            },

            placeNewOrder: function () {
                var self = this;

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

            confirm: function (methodType, confirmParams, clientSecret, isSetup, onConfirm, onFail) {
                onConfirm();
                // if (!clientSecret)
                //     return this.softCrash("To confirm the payment, a client secret is necessary, but we don't have one.");

                // if (methodType && methodType != 'new') {
                //     if (!confirmParams.payment_method)
                //         return this.softCrash("To confirm the payment, a saved payment method must be selected, but we don't have one.");

                //     if (isSetup) {
                //         if (methodType == "card")
                //             stripe.stripeJs.confirmCardSetup(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "sepa_debit")
                //             stripe.stripeJs.confirmSepaDebitSetup(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "boleto")
                //             stripe.stripeJs.confirmBoletoSetup(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "acss_debit")
                //             stripe.stripeJs.confirmAcssDebitSetup(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "us_bank_account")
                //             stripe.stripeJs.confirmUsBankAccountSetup(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else
                //             this.showError($t("This payment method is not supported."));
                //     }
                //     else {
                //         if (methodType == "card")
                //             stripe.stripeJs.confirmCardPayment(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "sepa_debit")
                //             stripe.stripeJs.confirmSepaDebitPayment(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "boleto")
                //             stripe.stripeJs.confirmBoletoPayment(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "acss_debit")
                //             stripe.stripeJs.confirmAcssDebitPayment(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else if (methodType == "us_bank_account")
                //             stripe.stripeJs.confirmUsBankAccountPayment(clientSecret, confirmParams).then(onConfirm, onFail);
                //         else
                //             this.showError($t("This payment method is not supported."));
                //     }
                // }
                // else {
                //     customerData.invalidate(['cart']);

                //     confirmParams = this.getConfirmParams();

                //     // Confirm the payment using element
                //     if (isSetup) {
                //         stripe.stripeJs.confirmSetup(confirmParams).then(onConfirm, onFail);
                //     }
                //     else {
                //         stripe.stripeJs.confirmPayment(confirmParams).then(onConfirm, onFail);
                //     }
                // }
            },

            onOrderPlaced: function (result, outcome, response) {
                if (!this.isOrderPlaced() && isNaN(result))
                    return this.softCrash("The order was placed but the response from the server did not include a numeric order ID.");
                else
                    this.isOrderPlaced(true);

                this.isLoading(true);
                var onConfirm = this.onConfirm.bind(this);
                var onFail = this.onFail.bind(this);

                this.confirm(null, null, null, null, onConfirm, null);

                // // Non-card based confirms may redirect the customer externally. We restore the quote just before it in case the
                // // customer clicks the back button on the browser before authenticating the payment.
                // var self = this;
                // restoreQuoteAction(function () {
                //     // If we are confirming the payment with a saved method, we need a client secret and a payment method ID
                //     var selectedMethod = self.getSelectedMethod("type");

                //     var clientSecret = self.getStripeParam("clientSecret");
                //     if (!clientSecret)
                //         return self.softCrash("To confirm the payment, a client secret is necessary, but we don't have one.");

                //     var isSetup = false;
                //     if (clientSecret.indexOf("seti_") === 0)
                //         isSetup = true;

                //     var confirmParams = {
                //         payment_method: self.getSelectedMethod("value"),
                //         return_url: self.getStripeParam("successUrl")
                //     };

                //     var dropDownSelection = self.selection();
                //     if (dropDownSelection && dropDownSelection.type == "card" && dropDownSelection.cvc == 1 && !isSetup) {
                //         confirmParams.payment_method_options = {
                //             card: {
                //                 cvc: self.cardCvcElement
                //             }
                //         };
                //     }

                //     self.confirm.bind(self)(selectedMethod, confirmParams, clientSecret, isSetup, onConfirm, onFail);
                // });
            },

            placeOrder: function () {
                console.log("@#@#@#")
                console.log("@#@#@#")
                this.messageContainer.clear();

                if (!this.isPaymentFormComplete())
                    return this.showError($t('Please complete your payment details.'));

                if (!this.validate())
                    return;

                // this.clearErrors();
                this.isPlaceOrderActionAllowed(false);
                this.isLoading(true);

                var placeNewOrder = this.placeNewOrder.bind(this);
                var reConfirmPayment = this.onOrderPlaced.bind(this);
                var self = this;

                console.log('this.isOrderPlaced()', this.isOrderPlaced())
                if (this.isOrderPlaced()) // The order was already placed once but the payment failed
                {
                    console.log("inside order placed");
                    updateCartAction(this.getPaymentMethodId(), function (result, outcome, response) {
                        self.isLoading(false);
                        try {
                            var data = JSON.parse(result);
                            console.log(data, 'data');
                            if (data.error) {
                                self.showError(data.error);
                            }
                            else if (data.redirect) {
                                $.mage.redirect(data.redirect);
                            }
                            else if (data.placeNewOrder) {
                                placeNewOrder();
                            }
                            else {
                                reConfirmPayment();
                            }
                        }
                        catch (e) {
                            self.showError($t("The order could not be placed. Please contact us for assistance."));
                            console.error(e.message);
                        }
                    });
                }
                else {
                    try {
                        console.log('placing new order');
                        placeNewOrder();
                    }
                    catch (e) {
                        self.showError($t("The order could not be placed. Please contact us for assistance."));
                        console.error(e.message);
                    }
                }

                return false;
            },

        });
    }
);