define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'mage/translate'
], function ($, ko, Component, quote, urlBuilder, storage, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'AlpineCommerce_LoyaltyProgram/loyalty-points'
        },

        initialize: function () {
            this._super();

            var config = window.checkoutConfig.loyaltyPoints || {};

            this.pointsAvailable = config.available || 0;
            this.redemptionRate = config.redemptionRate || 1;
            this.pointsUsed = ko.observable(0);
            this.isSyncing = ko.observable(false);
            this.syncMessage = ko.observable('');

            this.originalGrandTotal = this.getCurrentGrandTotal();

            this.pointsUsed.subscribe(function () {
                this.scheduleSync();
            }, this);

            return this;
        },

        /**
         * Debounced server sync so typing does not spam the API.
         */
        scheduleSync: function () {
            var self = this;

            clearTimeout(this.syncTimeout);
            this.syncTimeout = setTimeout(function () {
                self.sync();
            }, 500);
        },

        /**
         * Points used, clamped to the available balance.
         *
         * @return {Number}
         */
        getPointsUsed: function () {
            var used = parseInt(this.pointsUsed(), 10) || 0;

            return Math.max(0, Math.min(used, this.pointsAvailable));
        },

        /**
         * Discount amount in store currency, recalculated in real time.
         *
         * @return {Number}
         */
        getDiscount: function () {
            return this.getPointsUsed() * this.redemptionRate;
        },

        /**
         * @return {Number}
         */
        getCurrentGrandTotal: function () {
            var totals = quote.getTotals();

            if (totals && totals()) {
                return parseFloat(totals().grand_total) || 0;
            }

            return 0;
        },

        /**
         * Grand total estimated client-side before the server round-trip.
         *
         * @return {Number}
         */
        getEstimatedTotal: function () {
            return Math.max(0, this.originalGrandTotal - this.getDiscount());
        },

        /**
         * @return {*|String}
         */
        getFormattedDiscount: function () {
            return this.getFormattedPrice(-this.getDiscount());
        },

        /**
         * Send the used points to the server, which recomputes and applies the
         * real discount. The returned totals are pushed back into the quote so
         * the displayed grand total always matches the billed amount.
         */
        sync: function () {
            var self = this,
                points = this.getPointsUsed(),
                url = urlBuilder.createUrl('/carts/mine/loyalty-points', {});

            if (!window.checkoutConfig.isCustomerLoggedIn || !window.checkoutConfig.quoteData) {
                return;
            }

            this.isSyncing(true);

            storage.post(
                url,
                JSON.stringify({points: points}),
                false,
                'application/json'
            ).done(function (response) {
                quote.setTotals(response);
                self.originalGrandTotal = (parseFloat(response.grand_total) || 0) + self.getDiscount();
                self.syncMessage($t('Loyalty discount applied.'));
            }).fail(function () {
                self.syncMessage($t('Unable to apply loyalty discount.'));
            }).always(function () {
                self.isSyncing(false);
            });
        }
    });
});
