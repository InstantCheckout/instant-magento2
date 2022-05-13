define([
    "jquery",
    'Instant_Checkout/js/ic-helper'
], function ($, checkoutHelper) {
    "use strict";

    checkoutHelper.refreshInstantButtons();

    // Handling onMessages
    window.addEventListener('message', function (e) {
        try {
            const data = e.data;
            const dataObj = JSON.parse(data);

            switch (dataObj.type) {
                case 'clearCart': {
                    $.ajax({
                        url: window.location.origin + "/instant/cart/clear",
                        type: 'PUT',
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function () {
                            // do nothing
                        },
                        error: function () {
                            this.showErrorAlert();
                            return;
                        }
                    })
                }
            }
        } catch (err) { }
    });
});
