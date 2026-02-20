/**
 * Venbhas FilterMultiselect - Interactive Filter Navigation
 *
 * Handles checkbox change events and navigates to filter URLs
 * When a filter checkbox is toggled, the page reloads with the updated filter parameters
 */

define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        /**
         * Handle filter checkbox change events
         * Only observes checkboxes with class 'filter-checkbox'
         */
        $(element).on('change', 'input.filter-checkbox', function() {
            var $checkbox = $(this);
            var url = $checkbox.data('url');

            // Navigate to the URL if it exists
            if (url) {
                window.location.href = url;
            }
        });
    };
});
