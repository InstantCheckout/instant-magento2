define(['jquery', 'checkoutHelper',
], function ($, checkoutHelper) {
    'use strict';
    return function (widget) {
        $.widget('mage.priceBox', widget, {
            _init: function initPriceBox() {
                this._super();
                
                var box = this.element;
                box.trigger('updatePrice');
            },

            reloadPrice: function reDrawPrices() {
                this._super();

                _.each(this.cache.displayPrices, function (price) {
                    price.final = _.reduce(price.adjustments, function (memo, amount) {
                        return memo + amount;
                    }, price.amount);

                    const formData = checkoutHelper.parseFormEntries('#product_addtocart_form');
                    if (formData) {
                        const product = formData.find(d => d.attribute === 'product');
                        if (product) {
                            checkoutHelper.getProduct(product.value, null, (data) => {
                                const { sku, disabledForSkusContaining, disabledTotalThreshold } = data;

                                let isBlacklistedSku = false;
                                disabledForSkusContaining.forEach(x => {
                                    if (x && sku.indexOf(x) !== -1) {
                                        isBlacklistedSku = true;
                                    }
                                })
                                const productPriceExceedsThreshold = disabledTotalThreshold && parseFloat(disabledTotalThreshold) > 0 && parseFloat(price.final) > disabledTotalThreshold;
                                const skuIsDisabled = isBlacklistedSku || productPriceExceedsThreshold;

                                $('#instant-btn-product-page-container').css('display', skuIsDisabled ? 'none' : 'flex');
                                $('#instant-btn-product-page-container').css('flex-direction', 'column');
                            })
                        }
                    }
                }, this);
            },
        });
        return $.mage.priceBox;
    }
})
