/**
 * Venbhas FilterMultiselect — storefront behavior (Luma + Hyvä).
 * Loaded as a static asset (not inline) so Luma/CSP and script order cannot skip it.
 */
(function () {
    if (window.__venbhasFilterMultiselectInit) {
        return;
    }
    window.__venbhasFilterMultiselectInit = true;

    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('input.filter-checkbox')) {
            var url = e.target.dataset.url;
            if (url) {
                window.location.assign(url);
            }
        }
    });

    document.addEventListener(
        'click',
        function (e) {
            var link = e.target.closest && e.target.closest('a.action.remove');
            if (!link) {
                return;
            }
            var href = link.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            window.location.assign(href);
        },
        true
    );
})();
