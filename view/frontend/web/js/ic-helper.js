define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return {
        getInstantBaseUrl: function () {
            const isSandbox = Instant.config.enableSandbox;

            return 'http://' + (isSandbox ? '' : '') + 'localhost:3000/';
        },

        initializeInstant: function (callback) {
            $.ajax({
                url: window.location.origin + "/instant/data/getconfig",
                type: 'GET',
                cache: false,
                retryLimit: 3,
                contentType: false,
                processData: false,
                success: function (data) {
                    window.Instant = {};
                    window.Instant['config'] = data;

                    if (typeof callback === 'function') {
                        callback();
                    }
                },
                error: function () {
                    this.retryLimit--;
                    if (this.retryLimit) {
                        $.ajax(this);
                    }
                }
            })
        },

        showBackdrop: function (checkoutWindow) {
            const onClick = () => {
                if (checkoutWindow) {
                    checkoutWindow.focus();
                } else {
                    $('#ic-backdrop-container').css('display', 'none');
                }
            };

            $('#ic-backdrop-container').css('display', 'flex');
            $('.ic-backdrop').css('display', 'flex');
            $('.ic-backdrop-close').on('click', function () {
                $('#ic-backdrop-container').css('display', 'none');
            });
            $('.ic-backdrop-message').on('click', function () {
                onClick();
            });
            $('.ic-backdrop-continue').on('click', function () {
                onClick();
            });
        },

        isWindowInstant: function () {
            return window.Instant && window.Instant.config ? true : false;
        },

        handleInstantAwareFunc: function (func) {
            if (this.isWindowInstant()) {
                func();
            } else {
                console.log("Window is not Instant?")
                this.initializeInstant(func);
            }
        },

        getCheckoutUrl: function (items, cartId, source) {
            const merchantIdParam = 'merchantId=' + window.Instant.config.appId;
            const storeCodeParam = 'storeCode=' + window.Instant.config.storeCode;
            const sessionIdParam = Instant.config.sessId ? 'sessionId=' + Instant.config.sessId : '';
            const srcParam = "src=" + source;
            const confirmParam = "confirm=true";

            console.log(sessionIdParam);

            let url = this.getInstantBaseUrl() + '?' + confirmParam + '&' + storeCodeParam + '&' + merchantIdParam + '&' + sessionIdParam + '&' + srcParam;
            url = cartId ? url + '&' + 'cartId=' + cartId : url + '&' + 'items=' + encodeURIComponent(JSON.stringify(items));

            console.log(url);
            return url;
        },

        isClientMobileOrTablet: function () {
            let check = false;
            (function (a) { if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) check = true; })(navigator.userAgent || navigator.vendor || window.opera);
            return check;
        },

        showErrorAlert: function () {
            alert("An error occurred during checkout. Please try again.")
        },

        getCustomerCartData: function () {
            const customerDataCart = customerData.get('cart');
            if (!customerDataCart) {
                this.showErrorAlert();
                return;
            }

            const cartData = customerDataCart();
            if (!cartData) {
                this.showErrorAlert();
                return;
            }

            return cartData;
        },

        configurePdpBtn: function (shouldResizePdpBtn, height, borderRadius) {
            const pdpBtnContainerSelector = '#ic-pdp-btn-container';
            const pdpBtnSelector = '#ic-pdp-btn';
            const atcBtnSelector = '#product-addtocart-button';

            const resizePdpBtn = (shouldResize) => {
                if (!shouldResize) {
                    return;
                }

                $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                $(window).resize(function () {
                    $(pdpBtnContainerSelector).css('width', $(atcBtnSelector).outerWidth() + 'px');
                });
            };

            resizePdpBtn(shouldResizePdpBtn);
            $(pdpBtnContainerSelector).prependTo($(".box-tocart .fieldset .actions").first());

            if (height) {
                $(pdpBtnSelector).css('height', height + 'px');
            }
            if (borderRadius) {
                $(pdpBtnSelector).css('border-radius', borderRadius + 'px');
            }

            this.handleInstantAwareFunc(() => {
                const config = Instant.config;

                const configBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
                const configHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

                resizePdpBtn(config.shouldResizePdpBtn);

                if (!height) {
                    $(pdpBtnSelector).css('height', configHeight + 'px');
                }
                if (!borderRadius) {
                    $(pdpBtnSelector).css('border-radius', configBorderRadius + 'px');
                }
            });
        },

        setMinicartBtnAttributes: function (width, height, borderRadius) {
            const mcBtnContainerSelector = '#ic-mc-btn-container';
            const mcBtnWrapperSelector = '#ic-mc-btn-wrapper';
            const mcBtnSelector = '#ic-mc-btn';

            if (width) {
                $(mcBtnWrapperSelector).css('width', width + '%');
            }
            if (height) {
                $(mcBtnSelector).css('height', height + 'px');
            }
            if (borderRadius) {
                $(mcBtnSelector).css('border-radius', borderRadius + 'px');
            }

            this.handleInstantAwareFunc(() => {
                const config = Instant.config;

                const cartData = this.getCustomerCartData();
                const shouldEnableMinicartInstantBtn = cartData && cartData.items && cartData.items.length > 0 && config.enableMinicartBtn && this.shouldEnableInstantBtn();
                $(mcBtnContainerSelector).css('display', shouldEnableMinicartInstantBtn ? 'flex' : 'none');
                $(mcBtnSelector).prop('disabled', false);

                const configWidth = (config.mcBtnWidth && parseInt(config.mcBtnWidth) > 0) ? config.mcBtnWidth : "90";
                const configBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
                const configHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

                if (!width) {
                    $(mcBtnWrapperSelector).css('width', configWidth + '%');
                }
                if (!height) {
                    $(mcBtnSelector).css('height', configHeight + 'px');
                }
                if (!borderRadius) {
                    $(mcBtnSelector).css('border-radius', configBorderRadius + 'px');
                }
            });
        },

        setCartIndexBtnAttributes: function (shouldResize, height, borderRadius, refreshConfig = true) {
            const cartIndexBtnContainerSelector = '#ic-cindex-btn-container';
            const cartIndexBtnWrapperSelector = '#ic-cindex-btn-wrapper';
            const cartIndexBtnSelector = '#ic-cindex-btn';

            const resizeCartIndexBtn = (shouldResize) => {
                if (!shouldResize) {
                    return;
                }

                const primaryCheckoutBtnSelector = $("button.action.primary.checkout");
                let cartIndexJqueryEl = primaryCheckoutBtnSelector;

                if (primaryCheckoutBtnSelector.length > 1) {
                    cartIndexJqueryEl = primaryCheckoutBtnSelector.eq(1);
                }

                $(cartIndexBtnWrapperSelector).css('width', cartIndexJqueryEl.outerWidth() + 'px');
                $(window).resize(function () {
                    $(cartIndexBtnWrapperSelector).css('width', cartIndexJqueryEl.outerWidth() + 'px');
                });
            };

            resizeCartIndexBtn(shouldResize);

            if (height) {
                $(cartIndexBtnSelector).css('height', height + 'px');
            }
            if (borderRadius) {
                $(cartIndexBtnSelector).css('border-radius', borderRadius + 'px');
            }

            this.handleInstantAwareFunc(() => {
                const config = Instant.config;

                resizeCartIndexBtn(config.shouldResizeCartIndexBtn)
                $(cartIndexBtnContainerSelector).css('display', this.shouldEnableInstantBtn() ? 'flex' : 'none');
                $(cartIndexBtnSelector).prop('disabled', false);

                const configBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
                const configHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

                if (!height) {
                    $(cartIndexBtnSelector).css('height', configHeight + 'px');
                }
                if (!borderRadius) {
                    $(cartIndexBtnSelector).css('border-radius', configBorderRadius + 'px');
                }
            });
        },

        setCheckoutPageBtnAttributes: function (width, height, borderRadius) {
            const checkoutPageBtnContainerSelector = '#ic-cpage-btn-container';
            const checkoutPageBtnWrapperSelector = '#ic-cpage-btn-wrapper';
            const checkoutPageBtnSelector = '#ic-cpage-btn';

            if (width) {
                $(checkoutPageBtnWrapperSelector).css('width', width + '%');
            }
            if (height) {
                $(checkoutPageBtnSelector).css('height', height + 'px');
            }
            if (borderRadius) {
                $(checkoutPageBtnSelector).css('border-radius', borderRadius + 'px');
            }

            this.handleInstantAwareFunc(() => {
                const config = Instant.config;

                const shouldEnableInstantBtn = this.shouldEnableInstantBtn();

                const configWidth = (config.cpageBtnWidth && parseInt(config.cpageBtnWidth) > 0) ? config.cpageBtnWidth : "60";
                const configBorderRadius = (config.btnBorderRadius && parseInt(config.btnBorderRadius) >= 0 && parseInt(config.btnBorderRadius) <= 10) ? config.btnBorderRadius : "3";
                const configHeight = (config.btnHeight && parseInt(config.btnHeight) >= 40 && parseInt(config.btnHeight) <= 50) ? config.btnHeight : "45";

                if (!width) {
                    $(checkoutPageBtnWrapperSelector).css('width', configWidth + '%');
                }
                if (!height) {
                    $(checkoutPageBtnSelector).css('height', configHeight + 'px');
                }
                if (!borderRadius) {
                    $(checkoutPageBtnSelector).css('border-radius', configBorderRadius + 'px');
                }

                $(checkoutPageBtnContainerSelector).css('display', shouldEnableInstantBtn ? 'flex' : 'none');
                $(checkoutPageBtnSelector).prop('disabled', false);
            });
        },

        shouldEnableInstantBtn: function () {
            const cartData = this.getCustomerCartData();

            let cartContainsBlacklistedSku = false;

            if (cartData && cartData.items) {
                cartData.items.forEach(item => {
                    Instant.config.disabledForSkusContaining.forEach(x => {
                        if (x && item.product_sku.indexOf(x) !== -1) {
                            cartContainsBlacklistedSku = true;
                        }
                    })
                })
            }

            return !cartContainsBlacklistedSku && window.Instant.config.isGuest;
        },

        handleCartTotalChanged: function () {
            this.handleInstantAwareFunc(() => {
                this.configurePdpBtn();
                this.setMinicartBtnAttributes();
                this.setCartIndexBtnAttributes();
                this.setCheckoutPageBtnAttributes();
            })
        },

        openCheckoutWindow: function (url) {
            const windowHeight = 800;
            const windowWidth = 490;
            const posY = window.outerHeight / 2 + window.screenY - (windowHeight / 2);
            const posX = window.outerWidth / 2 + window.screenX - (windowWidth / 2);
            return window.open(url, '', 'location=yes,height=' + windowHeight + ',width=' + windowWidth + ',top=' + posY + ',left=' + posX + ',scrollbars=yes,status=yes');
        },

        init: function (items, cartId, source) {
            return this.openCheckoutWindow(this.getCheckoutUrl(items, cartId, source));
        },

        canBrowserSetWindowLocation: function () {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            const isFbOrInstaBrowser = (ua.indexOf("FBAN") > -1 || ua.indexOf("FBAV") > -1) || navigator.userAgent.includes("Instagram");
            return isFbOrInstaBrowser;
        },

        hideBackdrop: function () {
            $('#ic-backdrop-container').css('display', 'none');
        },

        checkoutCustomerCart: function (sourceLocation) {
            let checkoutWindow;
            if (window.Instant &&
                window.Instant.config &&
                window.Instant.config.checkoutConfig &&
                window.Instant.config.checkoutConfig.quoteData &&
                window.Instant.config.checkoutConfig.quoteData.entity_id &&
                window.Instant.config.checkoutConfig.quoteData.entity_id.length === 32) {
                checkoutWindow = this.init(null, window.Instant.config.checkoutConfig.quoteData.entity_id, sourceLocation);
            } else {
                if (!this.canBrowserSetWindowLocation()) {
                    checkoutWindow = this.openCheckoutWindow(this.getInstantBaseUrl());
                }

                this.handleInstantAwareFunc(() => {
                    const url = this.getCheckoutUrl(null, window.Instant.config.checkoutConfig.quoteData.entity_id, sourceLocation);

                    if (checkoutWindow) {
                        checkoutWindow.location = url;
                    } else {
                        window.location = url;
                    }
                });
            }

            if (checkoutWindow) {
                this.showBackdrop(checkoutWindow);
                const loop = setInterval(function () {
                    if (checkoutWindow.closed) {
                        $('#ic-backdrop-container').css('display', 'none');
                        clearInterval(loop);
                    }
                }, 500);
            }
        }
    };
});
