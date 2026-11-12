(function () {
    'use strict';

    const fieldSelector = '.form-group, .filter-group, .history-field, .billing-field, .overview-field, .report-filter-field, .pi-filter-field, .filter-field, .form-field';
    const icons = {
        search: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/><path d="m13 13 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
        filter: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5h14M5.5 10h9M8 15h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
        reset: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 8a6 6 0 1 1 .5 5M4 3v5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    };

    function wrapperFor(control, form) {
        const wrapper = control.closest(fieldSelector);
        if (wrapper && wrapper !== form) return wrapper;
        const field = document.createElement('div');
        const label = control.id && [...form.querySelectorAll('label')].find(item => item.htmlFor === control.id);
        control.before(field);
        if (label) field.append(label);
        field.append(control);
        return field;
    }

    function mergeTableTools(form) {
        const scope = form.closest('.billing-panel, .history-panel') || form.closest('.main-content');
        // Detail tables belong to their own disclosure, not the page-level search.
        const tools = [...(scope?.querySelectorAll('.pi-table-sortbar:not(.pi-unified-table-tools)') || [])]
            .find(root => !root.closest('details, dialog, [role="dialog"]'));
        if (!tools) return;
        tools.classList.add('pi-unified-table-tools');
        form.querySelector('.pi-compact-toolbar').append(tools);
    }

    function setup(form, index) {
        if (form.method.toLowerCase() !== 'get' || form.hasAttribute('data-native-compact') || form.dataset.compactFilters || form.dataset.filterGroups === 'off') return;
        const controls = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea')]
            .filter(control => !['submit', 'button', 'reset', 'file'].includes(control.type));
        if (!controls.length) return;
        // Keep a standalone year selector directly accessible.
        if (form.matches('.academic-year-filter')) {
            form.classList.add('pi-compact-year-filter');
            return;
        }
        form.dataset.compactFilters = 'true';
        const search = controls.find(control => control.matches('[type="search"], [name="search"], [name="q"], [name="keyword"]'));
        const fields = controls.filter(control => control !== search);
        const wrappers = [...new Set(fields.map(control => wrapperFor(control, form)))];
        const searchWrapper = search ? wrapperFor(search, form) : null;
        const submit = [...form.querySelectorAll('button, input[type="submit"]')]
            .find(button => button.type === 'submit');
        const reset = form.querySelector('a.reset-btn, a.history-reset-button, a[data-filter-reset], a.btn-secondary, a.btn-filter-secondary');
        const liveSearch = form.id === 'billingScopeForm' ? document.querySelector('.billing-search') : null;
        const statusFilters = form.id === 'billingScopeForm' ? document.querySelector('.billing-status-filters') : null;
        const toolbar = document.createElement('div');
        toolbar.className = 'pi-compact-toolbar';
        form.classList.add('pi-compact-filter-form');
        form.prepend(toolbar);

        const searchField = searchWrapper || liveSearch;
        if (searchField) {
            searchField.classList.add('pi-compact-search');
            toolbar.append(searchField);
            const input = search || liveSearch.querySelector('input');
            input.setAttribute('aria-label', input.labels?.[0]?.textContent.trim() || 'Cari siswa');
            input.title = input.placeholder;
            if (input.placeholder.length > 32) input.placeholder = liveSearch ? 'Cari nama, NIS, kelas…' : 'Cari data…';
            if (!searchField.querySelector(':scope > svg, :scope > i, .school-search-icon')) searchField.insertAdjacentHTML('afterbegin', icons.search);
            if (liveSearch) input.addEventListener('keydown', event => {
                if (event.key === 'Enter') event.preventDefault();
            });
            if (liveSearch) {
                input.name = 'table_search';
                input.value = new URLSearchParams(location.search).get('table_search') || '';
            }
        }

        let panel;
        let toggle;
        let count;
        let nativePopover = false;
        const isExpanded = () => !!panel && (nativePopover ? panel.matches(':popover-open') : !panel.hidden);
        const positionPanel = () => {
            if (!isExpanded()) return;
            const viewportWidth = document.documentElement.clientWidth;
            panel.style.setProperty('--pi-viewport-width', `${viewportWidth}px`);
            const trigger = toggle.getBoundingClientRect();
            const bounds = panel.getBoundingClientRect();
            panel.style.left = `${Math.max(10, Math.min(trigger.left, viewportWidth - bounds.width - 10))}px`;
            panel.style.top = `${Math.max(10, trigger.bottom + bounds.height + 8 <= innerHeight - 10 ? trigger.bottom + 8 : trigger.top - bounds.height - 8)}px`;
        };
        function setExpanded(expanded, focusToggle = false) {
            if (!panel) return;
            if (expanded) {
                panel.hidden = false;
                if (nativePopover && !isExpanded()) panel.showPopover();
                positionPanel();
            } else {
                if (nativePopover && isExpanded()) panel.hidePopover();
                panel.hidden = true;
            }
            toggle.setAttribute('aria-expanded', String(expanded));
            if (focusToggle) toggle.focus({ preventScroll: true });
        }
        if (fields.length || statusFilters) {
            panel = document.createElement('section');
            panel.className = 'pi-compact-filter-panel';
            panel.style.setProperty('--pi-filter-width', `${Math.min(520, wrappers.length * 220 + 26)}px`);
            panel.style.setProperty('--pi-filter-columns', Math.min(2, wrappers.length + (statusFilters ? 1 : 0)));
            panel.id = `compact-filter-${index}`;
            panel.setAttribute('aria-label', 'Pilihan filter');
            panel.setAttribute('popover', 'auto');
            panel.setAttribute('role', 'region');
            nativePopover = typeof panel.showPopover === 'function';
            if (!nativePopover) panel.removeAttribute('popover');
            panel.hidden = true;
            const heading = document.createElement('div');
            heading.className = 'pi-compact-filter-heading';
            heading.textContent = 'Filter data';
            const grid = document.createElement('div');
            grid.className = 'pi-compact-filter-grid';
            wrappers.forEach(wrapper => {
                wrapper.classList.add('pi-compact-field');
                grid.append(wrapper);
            });
            if (statusFilters) {
                const statusField = document.createElement('div');
                statusField.className = 'pi-compact-field';
                const label = document.createElement('label');
                label.htmlFor = `compact-status-${index}`;
                label.textContent = 'Status tagihan';
                const statusSelect = document.createElement('select');
                statusSelect.id = label.htmlFor;
                statusSelect.name = 'table_status';
                statusSelect.className = 'pi-compact-status-select';
                statusSelect.setAttribute('aria-label', 'Status tagihan');
                statusFilters.querySelectorAll('button').forEach(button => {
                    statusSelect.add(new Option(button.textContent.trim(), button.dataset.billingFilter, false, button.classList.contains('is-active')));
                });
                statusSelect.addEventListener('change', () => {
                    statusFilters.querySelector(`[data-billing-filter="${statusSelect.value}"]`)?.click();
                });
                const selectedStatus = new URLSearchParams(location.search).get('table_status');
                if ([...statusSelect.options].some(option => option.value === selectedStatus)) {
                    statusSelect.value = selectedStatus;
                    statusFilters.querySelector(`[data-billing-filter="${selectedStatus}"]`)?.click();
                }
                statusFilters.hidden = true;
                statusField.append(label, statusSelect, statusFilters);
                grid.append(statusField);
            }
            const actions = document.createElement('div');
            actions.className = 'pi-compact-panel-actions';
            const apply = document.createElement('button');
            apply.type = 'submit';
            apply.className = 'pi-compact-apply pi-tone-primary';
            apply.textContent = 'Terapkan filter';
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'pi-compact-close';
            close.textContent = 'Tutup';
            close.addEventListener('click', () => setExpanded(false, true));
            actions.append(close, apply);
            panel.append(heading, grid, actions);
            form.append(panel);
            toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'pi-compact-filter-toggle';
            toggle.setAttribute('aria-controls', panel.id);
            toggle.setAttribute('aria-expanded', 'false');
            toggle.innerHTML = `${icons.filter}<span>Filter</span><span class="pi-compact-filter-count"></span>`;
            count = toggle.querySelector('.pi-compact-filter-count');
            toggle.addEventListener('click', () => {
                const expanded = !isExpanded();
                setExpanded(expanded);
                if (expanded) panel.querySelector('input, select, button')?.focus({ preventScroll: true });
            });
            toolbar.append(toggle);
            panel.addEventListener('toggle', () => {
                toggle.setAttribute('aria-expanded', String(isExpanded()));
            });
            document.addEventListener('click', event => {
                if (!nativePopover && isExpanded() && !panel.contains(event.target) && !toggle.contains(event.target)) setExpanded(false);
            });
            window.addEventListener('resize', positionPanel);
            document.addEventListener('scroll', event => {
                if (!isExpanded() || panel.contains(event.target)) return;
                const trigger = toggle.getBoundingClientRect();
                if (trigger.bottom < 0 || trigger.top > innerHeight) setExpanded(false);
                else positionPanel();
            }, true);
            panel.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setExpanded(false, true);
                }
            });
        }

        if (search) {
            const searchButton = submit || document.createElement('button');
            searchButton.type = 'submit';
            searchButton.classList.add('pi-compact-search-submit');
            searchButton.setAttribute('aria-label', 'Cari data');
            searchButton.title = 'Cari data';
            searchButton.innerHTML = icons.search;
            searchWrapper.append(searchButton);
            searchWrapper.classList.add('pi-has-search-submit');
        } else {
            submit?.remove();
        }
        if (reset) {
            reset.classList.add('pi-compact-reset');
            reset.setAttribute('aria-label', 'Reset pencarian dan filter');
            reset.title = 'Reset pencarian dan filter';
            reset.innerHTML = icons.reset;
            toolbar.append(reset);
        }

        // Only the new toolbar and disclosure remain; retain validation messages and hidden inputs.
        form.querySelectorAll('.pi-filter-category-label, .pi-advanced-filter-panel, .pi-advanced-filter-toggle, .filter-title, .history-filter-title').forEach(element => element.remove());
        form.querySelectorAll('.pi-filter-grid, .filter-row, .filter-grid, .history-filter-grid, .overview-filter-grid, .pi-filter-actions, .filter-actions, .history-filter-footer, .filter-buttons').forEach(element => {
            if (!element.querySelector('input, select, textarea, button, a')) element.remove();
        });
        const shell = form.parentElement;
        if (shell.matches('.filter-card, .filter-section, .filter-container, .overview-filter-panel')) shell.classList.add('pi-compact-filter-shell');
        if (liveSearch) document.querySelector('.billing-table-tools')?.remove();

        const updateCount = () => {
            const active = fields.filter(control => ['checkbox', 'radio'].includes(control.type) ? control.checked : control.value.trim() !== '').length;
            if (reset) reset.hidden = !active && !search?.value.trim();
            if (!count) return;
            const status = statusFilters?.querySelector('[data-billing-filter].is-active');
            const total = active + (status && status.dataset.billingFilter !== 'all' ? 1 : 0);
            count.textContent = total ? String(total) : '';
            toggle.setAttribute('aria-label', total ? `Filter, ${total} aktif` : 'Filter');
            toggle.title = fields.filter(control => control.value).map(control => control.tagName === 'SELECT' ? control.selectedOptions[0]?.textContent.trim() : control.value).join(' · ') || 'Buka pilihan filter';
        };
        fields.forEach(control => control.addEventListener('change', updateCount));
        search?.addEventListener('input', updateCount);
        statusFilters?.addEventListener('click', () => window.setTimeout(updateCount, 0));
        updateCount();
        liveSearch?.querySelector('input').dispatchEvent(new Event('input', { bubbles: true }));
        mergeTableTools(form);
        // Validation must reveal the field before the browser tries to focus it.
        form.addEventListener('invalid', event => {
            if (panel?.contains(event.target)) setExpanded(true);
        }, true);
    }

    function initialize() {
        document.querySelectorAll('.main-content form.pi-filter-form, .main-content form.filter-form, .main-content form.billing-scope-form, .main-content form.history-filter-form, .main-content form.overview-filter-form').forEach(setup);
        document.addEventListener('permata:table-tools-ready', () => {
            document.querySelectorAll('form.pi-compact-filter-form').forEach(form => {
                if (!form.querySelector('.pi-unified-table-tools')) mergeTableTools(form);
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
