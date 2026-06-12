/**
 * Venbhas FilterMultiselect — Luma storefront behavior.
 * Expands layered-navigation accordion sections that contain active filters after page load.
 */
(function () {
    if (window.__venbhasFilterMultiselectInit) {
        return;
    }
    window.__venbhasFilterMultiselectInit = true;

    var escClass =
        typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
            ? function (s) {
                  return CSS.escape(s);
              }
            : function (s) {
                  return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
              };

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

    function getActiveFilterRequestVarsFromUrl() {
        var ignore = {
            p: 1,
            q: 1,
            id: 1,
            product_list_mode: 1,
            ___store: 1,
            ___from_store: 1,
            limit: 1,
            dir: 1,
            order: 1,
            form_key: 1,
        };
        var out = typeof Set !== 'undefined' ? new Set() : null;
        var arr = [];
        var search = window.location.search;
        if (!search || search.length < 2) {
            return out || arr;
        }
        try {
            new URLSearchParams(search).forEach(function (val, key) {
                var base = key.split('[')[0];
                if (!base || ignore[base]) {
                    return;
                }
                if (val === '' || val === null) {
                    return;
                }
                if (out) {
                    out.add(base);
                } else if (arr.indexOf(base) === -1) {
                    arr.push(base);
                }
            });
        } catch (eUrl) {
            /* ignore */
        }
        return out || arr;
    }

    function urlActiveVarsHas(activeVars, requestVar) {
        if (!requestVar || !activeVars) {
            return false;
        }
        if (typeof activeVars.has === 'function') {
            return activeVars.has(requestVar);
        }
        return activeVars.indexOf(requestVar) !== -1;
    }

    function lumaOpenSection(item) {
        if (!item || !item.classList) {
            return;
        }
        item.classList.add('_filter-active', 'venbhas-expand');
        var content = item.querySelector('[data-role="content"]');
        if (content) {
            content.style.display = 'block';
            content.style.visibility = 'visible';
            content.classList.remove('hidden', 'invisible');
            content.removeAttribute('hidden');
        }
        var title = item.querySelector('[data-role="title"]');
        if (title) {
            title.setAttribute('aria-expanded', 'true');
            title.classList.add('active');
        }
    }

    function expandFromNode(start) {
        var el = start;
        var guard = 0;
        while (el && el !== document.body && guard < 35) {
            if (el.classList && el.classList.contains('filter-options-item')) {
                lumaOpenSection(el);
            }
            if (el.tagName === 'DETAILS') {
                el.open = true;
            }
            el = el.parentElement;
            guard++;
        }
    }

    function expandFilterListsMatchingUrlRequestVars(activeVars) {
        if (!activeVars) {
            return;
        }
        if (typeof activeVars.size !== 'undefined' && activeVars.size === 0) {
            return;
        }
        if (Array.isArray(activeVars) && activeVars.length === 0) {
            return;
        }

        document.querySelectorAll('ol.venbhas-filter-multiselect[data-venbhas-request-var]').forEach(function (ol) {
            var rv = ol.getAttribute('data-venbhas-request-var');
            if (!rv || !urlActiveVarsHas(activeVars, rv)) {
                return;
            }
            expandFromNode(ol);
        });
    }

    function expandSwatchSectionsFromUrl() {
        var search = window.location.search;
        if (!search || search.length < 2) {
            return;
        }
        var params;
        try {
            params = new URLSearchParams(search);
        } catch (e) {
            return;
        }
        params.forEach(function (val, key) {
            var base = key.split('[')[0];
            if (!base) {
                return;
            }
            var sw =
                document.querySelector('.swatch-layered.' + escClass(base)) ||
                document.querySelector(
                    '.swatch-layered[data-attribute-code="' + String(base).replace(/"/g, '') + '"]'
                );
            if (sw) {
                expandFromNode(sw);
            }
        });
    }

    function expandActiveFilterSections() {
        expandFilterListsMatchingUrlRequestVars(getActiveFilterRequestVarsFromUrl());

        var seen = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

        function run(root) {
            if (!root || (seen && seen.has(root))) {
                return;
            }
            if (seen) {
                seen.add(root);
            }
            expandFromNode(root);
        }

        document.querySelectorAll('.venbhas-filter-multiselect[data-venbhas-has-active="1"]').forEach(run);
        document.querySelectorAll('input.filter-checkbox:checked').forEach(run);
        document.querySelectorAll('.filter-options-item._filter-active').forEach(lumaOpenSection);
        document.querySelectorAll('.venbhas-filter-multiselect .item.active').forEach(function (li) {
            run(li);
        });
        expandSwatchSectionsFromUrl();

        if (typeof window.require === 'function') {
            window.require(['jquery'], function ($) {
                var $list = $('#narrow-by-list');
                if (!$list.length) {
                    return;
                }
                var w = $list.data('mageAccordion') || $list.data('accordion');
                if (!w || typeof w.option !== 'function') {
                    return;
                }
                var indices = [];
                $list.children('.filter-options-item').each(function (idx) {
                    if (this.classList.contains('_filter-active') || this.classList.contains('venbhas-expand')) {
                        indices.push(idx);
                    }
                });
                if (!indices.length) {
                    return;
                }
                try {
                    if (indices.length > 1) {
                        w.option('multipleCollapsible', true);
                    }
                    w.option('active', indices.length === 1 ? indices[0] : indices);
                } catch (e) {
                    /* ignore */
                }
            });
        }
    }

    function scheduleExpand() {
        expandActiveFilterSections();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleExpand);
    } else {
        scheduleExpand();
    }

    window.addEventListener('load', scheduleExpand);

    [0, 50, 150, 400, 800, 1600].forEach(function (ms) {
        setTimeout(scheduleExpand, ms);
    });
})();
