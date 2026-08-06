// review-form.js - Handles review submission and helpful voting
define([
    'jquery',
    'mage/utils',
    'mage/mage'
], function ($, utils) {
    'use strict';

    return {
        config: {
            reviewForm: {
                submitButton: '#submit-review',
                form: '#review-form'
            }
        },

        init: function (submitBtnSelector) {
            var self = this;

            $(submitBtnSelector).on('click', function () {
                self.submitReview();
            });

            $('.vote-yes, .vote-no').on('click', function () {
                var reviewId = $(this).data('review-id');
                var helpful = $(this).data('helpful');
                self.voteHelpful(reviewId, helpful);
            });
        },

        submitReview: function () {
            var title = $('#review-title').val();
            var detail = $('#review-detail').val();
            var rating = $('input[name="rating"]:checked').val();
            var productId = $('input[name="product_id"]').val();

            if (!title || !detail || !rating) {
                alert('Please fill in all required fields and select a rating.');
                return;
            }

            $.ajax({
                url: '/rest/V1/alphacommerce/product-reviews',
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
        },

        voteHelpful: function (reviewId, helpful) {
            $.ajax({
                url: '/rest/V1/alphacommerce/product-reviews/' + reviewId + '/vote',
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
        }
    };
});
