(function () {
    'use strict';

    // Filter yang paling sering dipakai tetap terlihat sebagai filter umum.
    const commonFilterNames = new Set([
        'search',
        'q',
        'keyword',
        'sekolah',
        'sekolah_id',
        'kelas',
        'kelas_id',
        'tahun',
        'tahun_ajaran',
        'tahun_ajaran_id',
    ]);

    const fieldWrapperSelector = [
        '.form-group',
        '.filter-group',
        '.history-field',
        '.billing-field',
        '.overview-field',
        '.report-filter-field',
        '.pi-filter-field',
        '.filter-field',
        '.form-field',
    ].join(',');

    const actionSelector = [
        '.history-filter-footer',
        '.filter-actions',
        '.filter-buttons',
        '.overview-filter-buttons',
        '.report-filter-actions',
    ].join(',');

    const candidateControls = (form) => [...form.querySelectorAll('input[name], select[name], textarea[name]')]
        .filter((control) => {
            const type = (control.getAttribute('type') || '').toLowerCase();

            return !control.disabled
                && !['hidden', 'submit', 'button', 'reset', 'image', 'file'].includes(type)
                && !control.name.startsWith('_');
        });

    const fieldWrapper = (control, form) => {
        const explicitWrapper = control.closest(fieldWrapperSelector);

        if (explicitWrapper && explicitWrapper !== form) {
            return explicitWrapper;
        }

        let node = control.parentElement;
        let depth = 0;

        while (node && node !== form && depth < 3) {
            if (node.tagName === 'DIV' && node.querySelector('label')) {
                return node;
            }

            node = node.parentElement;
            depth += 1;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'pi-filter-field';
        control.parentElement.insertBefore(wrapper, control);
        wrapper.appendChild(control);

        return wrapper;
    };

    const hasValue = (control) => {
        if (control.type === 'checkbox' || control.type === 'radio') {
            return control.checked;
        }

        return String(control.value ?? '').trim() !== '';
    };

    const directChildOfForm = (element, form) => {
        let current = element;

        while (current && current.parentElement !== form) {
            current = current.parentElement;
        }

        return current?.parentElement === form ? current : null;
    };

    const setupFilterForm = (form, formIndex) => {
        if (form.dataset.filterGroups === 'off' || form.classList.contains('pi-filter-grouped')) {
            return;
        }

        const controls = candidateControls(form);
        const specialControls = controls.filter((control) => !commonFilterNames.has(control.name));

        if (specialControls.length === 0) {
            return;
        }

        const specialWrappers = [...new Set(
            specialControls.map((control) => fieldWrapper(control, form)).filter(Boolean)
        )];

        if (specialWrappers.length === 0) {
            return;
        }

        form.classList.add('pi-filter-grouped');

        const generalLabel = document.createElement('div');
        generalLabel.className = 'pi-filter-category-label';
        generalLabel.innerHTML = [
            '<span class="pi-filter-category-title">',
            '  <i class="fas fa-sliders" aria-hidden="true"></i>',
            '  <span>Filter umum</span>',
            '</span>',
        ].join('');

        const firstLayoutGroup = directChildOfForm(form.querySelector([
            '.history-filter-grid',
            '.filter-grid',
            '.filter-row',
            '.billing-filter-grid',
            '.overview-filter-grid',
            '.report-filter-grid',
        ].join(',')), form);
        form.insertBefore(generalLabel, firstLayoutGroup || form.firstChild);

        const panelId = `advanced-filter-${formIndex}`;
        const toggle = document.createElement('button');
        toggle.className = 'pi-advanced-filter-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-controls', panelId);
        toggle.innerHTML = [
            '<i class="fas fa-chevron-right pi-filter-chevron" aria-hidden="true"></i>',
            '<span>Filter khusus</span>',
            '<span class="pi-advanced-filter-count" aria-label="Filter khusus aktif"></span>',
        ].join('');
        if (firstLayoutGroup) {
            firstLayoutGroup.classList.add('pi-filter-primary-grid');
            const resetAnchor = firstLayoutGroup.querySelector('[data-filter-reset]');
            firstLayoutGroup.insertBefore(toggle, resetAnchor || null);
        } else {
            generalLabel.appendChild(toggle);
        }

        const panel = document.createElement('section');
        panel.id = panelId;
        panel.className = 'pi-advanced-filter-panel';
        panel.innerHTML = [
            '<div class="pi-advanced-filter-panel-header">',
            '  <div><strong>Filter khusus</strong><span>Gunakan bila hasil perlu dipersempit lebih detail.</span></div>',
            '</div>',
            '<div class="pi-advanced-filter-grid"></div>',
        ].join('');

        const grid = panel.querySelector('.pi-advanced-filter-grid');
        specialWrappers.forEach((wrapper) => grid.appendChild(wrapper));

        const actionAnchor = directChildOfForm(form.querySelector(actionSelector), form)
            || [...form.children].find((child) => child.matches?.('button[type="submit"], input[type="submit"]'))
            || null;

        form.insertBefore(panel, actionAnchor);

        const activeCountElement = toggle.querySelector('.pi-advanced-filter-count');
        const updateActiveCount = () => {
            const activeCount = specialControls.filter(hasValue).length;
            activeCountElement.textContent = activeCount > 0 ? `${activeCount} aktif` : '';
            return activeCount;
        };

        const setExpanded = (expanded) => {
            panel.hidden = !expanded;
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        };

        updateActiveCount();
        setExpanded(false);
        toggle.addEventListener('click', () => {
            setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
        });

        specialControls.forEach((control) => {
            control.addEventListener('change', updateActiveCount);
            control.addEventListener('input', updateActiveCount);
        });
    };

    const initialize = () => {
        [...document.querySelectorAll('form')]
            .filter((form) => form.hasAttribute('method') && form.getAttribute('method').toUpperCase() === 'GET')
            .forEach(setupFilterForm);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
