define([
    'jquery',
    'mage/utils',
    'mage/mage'
], function ($) {
    'use strict';
    return function (config, element) {
        var submitBtnSelector = config.submitBtn || '#submit-review';
        var submitUrl = config.submitUrl || '/rest/V1/alphacommerce/product-reviews';
        var voteBaseUrl = config.voteBaseUrl || '/rest/V1/alphacommerce/product-reviews';

        $(submitBtnSelector).on('click', function () {
            var title = $('#review-title').val();
            var detail = $('#review-detail').val();
            var rating = $('input[name="rating"]:checked').val();
            var productId = $('input[name="product_id"]').val();

            if (!title || !detail || !rating) {
                alert('Please fill in all required fields and select a rating.');
                return;
            }

            $.ajax({
                url: submitUrl,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    productId: parseInt(productId),
                    title: title,
                    detail: detail,
                    rating: parseInt(rating)
                }),
                success: function () {
                    alert('Your review has been submitted and is pending approval.');
                    window.location.reload();
                },
                error: function (xhr) {
                    alert('Error submitting review: ' + xhr.responseText);
                }
            });
        });

        $('.vote-yes, .vote-no').on('click', function () {
            var reviewId = $(this).data('review-id');
            var helpful = $(this).data('helpful');
            $.ajax({
                url: voteBaseUrl + '/' + reviewId + '/vote',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ helpful: helpful }),
                success: function () {
                    window.location.reload();
                },
                error: function (xhr) {
                    alert('Error recording vote: ' + xhr.responseText);
                }
            });
        });
    };
});
