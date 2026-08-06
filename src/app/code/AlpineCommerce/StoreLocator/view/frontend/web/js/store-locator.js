define([
    'mage/translate'
], function ($t) {
    'use strict';

    return function (config, element) {
        var input = element.querySelector('#store-locator-search');
        var cards = Array.prototype.slice.call(element.querySelectorAll('.store-card'));
        var noResults = element.querySelector('.store-locator-no-results');
        var resultCount = element.querySelector('.store-locator-result-count');

        if (!input || !cards.length) {
            return;
        }

        function filterStores() {
            var query = input.value.trim().toLocaleLowerCase();
            var visible = 0;

            cards.forEach(function (card) {
                var matches = !query || card.getAttribute('data-store-search').indexOf(query) !== -1;

                card.hidden = !matches;
                visible += matches ? 1 : 0;
            });

            noResults.hidden = visible !== 0;
            resultCount.textContent = visible + ' ' + $t(visible === 1 ? 'store found' : 'stores found');
        }

        input.addEventListener('input', filterStores);
        filterStores();
    };
});
