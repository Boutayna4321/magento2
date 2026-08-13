define([
    'jquery',
    'mage/mage'
], function ($) {
    'use strict';
    return function (config, element) {
        var submitUrl = config.submitUrl || '/rest/V1/alphacommerce/product-questions';
        var voteBaseUrl = config.voteBaseUrl || '/rest/V1/alphacommerce/product-questions';

        $(element).find('.vote-yes').on('click', function () {
            var questionId = $(this).data('question-id');
            $.ajax({
                url: voteBaseUrl + '/' + questionId + '/vote',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ helpful: 1 }),
                success: function () {
                    window.location.reload();
                },
                error: function (xhr) {
                    alert('Error recording vote: ' + xhr.responseText);
                }
            });
        });

        if ($('#submit-question').length) {
            $('#submit-question').on('click', function () {
                var question = $('#question-text').val();
                var productId = $('input[name="product_id"]').val();
                if (!question.trim()) {
                    alert('Please enter your question.');
                    return;
                }
                $.ajax({
                    url: submitUrl,
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
                        alert('Error recording vote: ' + xhr.responseText);
                    }
                });
            });
        }
    };
});
