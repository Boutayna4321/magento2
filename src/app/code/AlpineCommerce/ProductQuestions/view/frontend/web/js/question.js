define([
    'jquery'
], function ($) {
    'use strict';

    return {
        init: function () {
            var self = this;

            $('#submit-question').on('click', function () {
                var question = $('#question-text').val();
                var productId = $('input[name="product_id"]').val();

                if (!question.trim()) {
                    alert('Please enter your question.');
                    return;
                }

                $.ajax({
                    url: '/rest/V1/alphacommerce/product-questions',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        productId: parseInt(productId),
                        question: question
                    }),
                    success: function () {
                        alert('Your question has been submitted and is pending approval.');
                        window.location.reload();
                    },
                    error: function (xhr) {
                        alert('Error submitting question: ' + xhr.responseText);
                    }
                });
            });

            $('.vote-yes').on('click', function () {
                var questionId = $(this).data('question-id');
                self.voteHelpful(questionId, 1);
            });
        },

        voteHelpful: function (questionId, helpful) {
            $.ajax({
                url: '/rest/V1/alphacommerce/product-questions/' + questionId + '/vote',
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
