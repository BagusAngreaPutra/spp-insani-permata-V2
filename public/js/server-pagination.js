(() => {
    'use strict';
    const start = (scope = document) => {
        const tables = scope.matches?.('table[data-server-paginate]') ? [scope] : scope.querySelectorAll('table[data-server-paginate]');
        tables.forEach(table => {
            const panel = document.getElementById(table.dataset.piOptionsPanel || '');
            if (!panel || panel.querySelector('[data-server-size]')) return;
            const label = document.createElement('label');
            label.className = 'pi-page-size';
            label.textContent = 'Baris per halaman';
            const select = document.createElement('select');
            select.setAttribute('aria-label', 'Baris per halaman');
            select.dataset.serverSize = 'true';
            [10, 25, 50, 100].forEach(size => select.add(new Option(String(size), String(size))));
            select.value = table.dataset.perPage || '25';
            select.addEventListener('change', () => {
                const url = new URL(location.href);
                url.searchParams.set('per_page', select.value);
                url.searchParams.delete('page');
                url.searchParams.delete('print');
                const event = new CustomEvent('permata:server-navigate', { bubbles: true, cancelable: true, detail: { url } });
                if (table.dispatchEvent(event)) location.assign(url);
            });
            label.append(select);
            const print = document.createElement('a');
            const url = new URL(location.href);
            url.searchParams.set('print', '1');
            url.searchParams.delete('page');
            print.href = url;
            print.target = '_blank';
            print.rel = 'noopener';
            print.textContent = 'Cetak semua hasil';
            print.className = 'pi-print-all';
            panel.append(label, print);
        });
        const current = new URL(location.href);
        document.querySelectorAll('form[method="GET" i]').forEach(form => {
            if (form.closest('.main-content') && tables.length) {
                ['per_page', 'sort', 'direction'].forEach(key => {
                    if (!current.searchParams.has(key) || form.querySelector(`[name="${key}"]`)) return;
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = key; input.value = current.searchParams.get(key);
                    form.append(input);
                });
            }
        });
        if (scope === document && tables.length && current.searchParams.get('print') === '1') {
            const print = () => requestAnimationFrame(() => document.fonts.ready.then(() => window.print()));
            if (document.readyState === 'complete') print();
            else window.addEventListener('load', print, { once: true });
        }
    };
    document.addEventListener('permata:table-tools-ready', event => start(event.detail.table));
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => start(), { once: true });
    else start();
})();
