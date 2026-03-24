/**
 * Venbhas FilterMultiselect - Interactive Filter Navigation
 *
 * Handles checkbox change events and navigates to filter URLs
 * When a filter checkbox is toggled, the page reloads with the updated filter parameters
 */

define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        if (window.__venbhasFilterMultiselectRequireJsBound) {
            return;
        }
        window.__venbhasFilterMultiselectRequireJsBound = true;

        /**
         * Delegated handler (element is usually body on Luma).
         */
        $(element).on('change', 'input.filter-checkbox', function() {
            var $checkbox = $(this);
            var url = $checkbox.data('url');

            if (url) {
                window.location.href = url;
            }
        });
    };
});
