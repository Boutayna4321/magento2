define([
    'jquery',
    'mage/mage'
], function ($) {
    'use strict';
    return function (config, element) {
        $(element).find('#edit_form').mage('form').mage('validation', {
            validationUrl: config.validationUrl,
            highlight: function (element) {
                $(element).trigger('highlight.validate');
            }
        });
    };
});
