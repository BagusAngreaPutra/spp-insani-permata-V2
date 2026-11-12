/* Bind the server-rendered toolbar without rebuilding its visible structure. */
(() => {
    'use strict';
    const start = () => {
        const form = document.querySelector('#billingScopeForm[data-native-compact]');
        if (!form) return;
        const panel = form.querySelector('.pi-compact-filter-panel');
        const toggle = form.querySelector('.pi-compact-filter-toggle');
        const count = form.querySelector('.pi-compact-filter-count');
        const fields = [...panel.querySelectorAll('select')];
        const native = typeof panel.showPopover === 'function';
        if (!native) { panel.removeAttribute('popover'); panel.hidden = true; }
        const isOpen = () => native ? panel.matches(':popover-open') : !panel.hidden;
        const position = () => {
            if (!isOpen()) return;
            const viewport = document.documentElement.clientWidth;
            panel.style.setProperty('--pi-viewport-width', `${viewport}px`);
            const trigger = toggle.getBoundingClientRect();
            const bounds = panel.getBoundingClientRect();
            panel.style.left = `${Math.max(10, Math.min(trigger.left, viewport - bounds.width - 10))}px`;
            panel.style.top = `${Math.max(10, trigger.bottom + bounds.height + 8 <= innerHeight - 10 ? trigger.bottom + 8 : trigger.top - bounds.height - 8)}px`;
        };
        const close = (restoreFocus = false) => {
            if (native) { if (isOpen()) panel.hidePopover(); }
            else panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) toggle.focus({ preventScroll: true });
        };
        // Replace the declarative action only once the enhanced behaviour is available.
        toggle.removeAttribute('popovertarget');
        toggle.addEventListener('click', () => {
            if (isOpen()) { close(); return; }
            if (native) panel.showPopover();
            else panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            position();
            fields[0]?.focus({ preventScroll: true });
        });
        panel.addEventListener('toggle', () => toggle.setAttribute('aria-expanded', String(isOpen())));
        panel.querySelector('.pi-compact-close').removeAttribute('popovertarget');
        panel.querySelector('.pi-compact-close').addEventListener('click', () => close(true));
        panel.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); close(true); }
        });
        window.addEventListener('resize', position);
        document.addEventListener('scroll', event => {
            if (!isOpen() || panel.contains(event.target)) return;
            const trigger = toggle.getBoundingClientRect();
            if (trigger.bottom < 0 || trigger.top > innerHeight) close();
            else position();
        }, true);
        if (!native) document.addEventListener('click', event => {
            if (!panel.contains(event.target) && !toggle.contains(event.target)) close();
        });
        const updateCount = () => {
            const active = fields.filter(field => field.value && (field.name !== 'table_status' || field.value !== 'all')).length;
            count.textContent = active ? String(active) : '';
            toggle.setAttribute('aria-label', active ? `Filter, ${active} aktif` : 'Filter');
            toggle.title = fields.filter(field => field.value && field.value !== 'all').map(field => field.selectedOptions[0]?.textContent.trim()).join(' · ') || 'Buka pilihan filter';
        };
        fields.forEach(field => field.addEventListener('change', updateCount));
        form.addEventListener('submit', () => form.querySelector('[name="page"]')?.remove());
        form.addEventListener('invalid', () => {
            if (native && !isOpen()) panel.showPopover();
            else if (!native) panel.hidden = false;
            position();
        }, true);
        updateCount();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
