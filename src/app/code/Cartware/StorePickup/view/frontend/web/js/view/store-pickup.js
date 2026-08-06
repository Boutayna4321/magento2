define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'mage/translate'
], function (ko, Component, quote, urlBuilder, storage, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cartware_StorePickup/store-pickup'
        },

        initialize: function () {
            this._super();

            var config = window.checkoutConfig.storePickup || {};

            this.carrierCode = config.carrierCode || 'storepickup';
            this.availableStores = ko.observableArray(config.availableStores || []);
            this.selectedSourceCode = ko.observable();
            this.isSaving = ko.observable(false);
            this.syncMessage = ko.observable('');

            this.isPickupMethod = ko.computed(function () {
                var method = quote.shippingMethod();
                return !!(method && method['carrier_code'] === this.carrierCode);
            }, this);

            this.isVisible = ko.computed(function () {
                return this.isPickupMethod() && this.availableStores().length > 0;
            }, this);

            this.selectedStoreInfo = ko.computed(function () {
                var code = this.selectedSourceCode();
                var store = this.findStore(code);
                return store ? store.street + ', ' + store.city + ' - ' + store.phone : '';
            }, this);

            // Preserve the choice across shipping-method re-selection: the quote
            // field is only cleared by the component when switching to another carrier.
            this.isPickupMethod.subscribe(function (isPickup) {
                if (!isPickup) {
                    this.saveStore('');
                }
            }, this);

            return this;
        },

        findStore: function (code) {
            var stores = this.availableStores();
            for (var i = 0; i < stores.length; i++) {
                if (stores[i].source_code === code) {
                    return stores[i];
                }
            }
            return null;
        },

        saveStore: function () {
            var self = this,
                code = this.selectedSourceCode() || '',
                url = urlBuilder.createUrl('/carts/mine/store-pickup', {});

            if (!window.checkoutConfig.isCustomerLoggedIn || !window.checkoutConfig.quoteData) {
                return;
            }

            this.isSaving(true);

            storage.post(
                url,
                JSON.stringify({sourceCode: code}),
                false,
                'application/json'
            ).done(function () {
                self.syncMessage($t('Pickup store saved.'));
            }).fail(function () {
                self.syncMessage($t('Unable to save pickup store.'));
            }).always(function () {
                self.isSaving(false);
            });
        }
    });
});
