define([
    "jquery",
    "checkoutHelper"
], function ($, checkoutHelper) {
    "use strict";

    // Initialise config and cache values
    checkoutHelper.initializeInstant();

    // Handling onMessages
    window.onmessage = function (e) {
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
                        document.location.reload();
                    },
                    error: function () {
                        this.showErrorAlert();
                        return;
                    }
                })
            }
        }
    }
});
