(() => {
    'use strict';

    const start = () => {
        document.querySelectorAll('table[data-paginate]').forEach((table, tableIndex) => {
            if (table.hasAttribute('data-server-paginate')) return;
            const body = table.tBodies[0];
            if (!body) return;
            const dataRows = () => [...body.rows].filter(row => !row.querySelector('[colspan]') && !row.matches('[data-sort-fixed], .pi-sort-fixed'));
            if (dataRows().length <= 10) return;

            let page = 1;
            let size = window.matchMedia('(max-width: 768px)').matches ? 10 : 25;
            const storageKey = `permata:table-page:${location.pathname}${location.search}:${tableIndex}`;
            try {
                const saved = JSON.parse(sessionStorage.getItem(storageKey));
                if (saved && [10, 25, 50, 100, 'all'].includes(saved.size)) {
                    size = saved.size === 'all' ? Infinity : saved.size;
                    page = Number.isInteger(saved.page) && saved.page > 0 ? saved.page : 1;
                }
            } catch (_) { /* Navigation also works when storage is unavailable. */ }
            const toolbar = document.getElementById(table.dataset.piToolbar);
            const optionsPanel = document.getElementById(table.dataset.piOptionsPanel);
            const sizeLabel = document.createElement('label');
            sizeLabel.className = 'pi-page-size';
            sizeLabel.innerHTML = `Baris per halaman
                <select aria-label="Baris per halaman">
                    <option value="10">10</option><option value="25">25</option>
                    <option value="50">50</option><option value="100">100</option>
                    <option value="all">Semua</option>
                </select>`;
            const sizeSelect = sizeLabel.querySelector('select');
            if (optionsPanel) optionsPanel.append(sizeLabel);
            const controls = [];
            const createControls = (position) => {
                const root = document.createElement('nav');
                root.className = 'pi-table-pagination no-print';
                root.setAttribute('aria-label', `Halaman tabel ${tableIndex + 1}, ${position}`);
                root.innerHTML = `
                    <span class="pi-page-status" role="status" aria-live="polite"></span>
                    <div class="pi-page-buttons">
                        <button type="button" class="pi-page-previous" aria-label="Halaman sebelumnya" title="Halaman sebelumnya">&#8249;</button>
                        <span class="pi-page-number"></span>
                        <button type="button" class="pi-page-next" aria-label="Halaman berikutnya" title="Halaman berikutnya">&#8250;</button>
                    </div>`;
                const control = {
                    root,
                    status: root.querySelector('.pi-page-status'),
                    number: root.querySelector('.pi-page-number'),
                    previous: root.querySelector('.pi-page-previous'),
                    next: root.querySelector('.pi-page-next'),
                };
                const move = (direction) => {
                    page += direction;
                    render();
                    (toolbar || table).scrollIntoView({ block: 'start' });
                };
                control.previous.addEventListener('click', () => move(-1));
                control.next.addEventListener('click', () => move(1));
                controls.push(control);
                return root;
            };
            const render = () => {
                const rows = dataRows();
                const matches = rows.filter(row => !row.hidden);
                const pages = Number.isFinite(size) ? Math.max(1, Math.ceil(matches.length / size)) : 1;
                page = Math.max(1, Math.min(page, pages));
                const first = Number.isFinite(size) ? (page - 1) * size : 0;
                const last = Number.isFinite(size) ? Math.min(first + size, matches.length) : matches.length;
                rows.forEach(row => row.removeAttribute('data-page-hidden'));
                matches.forEach((row, index) => {
                    if (index < first || index >= last) row.setAttribute('data-page-hidden', '');
                });
                controls.forEach(control => {
                    control.root.hidden = pages <= 1;
                    control.status.textContent = `${matches.length ? first + 1 : 0}–${last} dari ${matches.length}`;
                    control.number.textContent = `${page} / ${pages}`;
                    control.number.setAttribute('aria-label', `Halaman ${page} dari ${pages}`);
                    control.previous.disabled = page <= 1;
                    control.next.disabled = page >= pages;
                });
                sizeSelect.value = Number.isFinite(size) ? String(size) : 'all';
                const meta = toolbar?.querySelector('.pi-table-meta');
                if (meta) meta.textContent = `${matches.length.toLocaleString('id-ID')} data`;
                try {
                    sessionStorage.setItem(storageKey, JSON.stringify({ page, size: Number.isFinite(size) ? size : 'all' }));
                } catch (_) { /* Persistence is optional. */ }
            };

            table.after(createControls('bawah'));
            if (!optionsPanel) controls[0].root.prepend(sizeLabel);
            sizeSelect.addEventListener('change', () => {
                size = sizeSelect.value === 'all' ? Infinity : Number(sizeSelect.value);
                page = 1;
                render();
            });
            render();
            table.addEventListener('permata:table-sorted', () => { page = 1; render(); });

            // Only filtering/row changes reset the page; pagination attributes do not.
            const observer = new MutationObserver(mutations => {
                const rowsChanged = mutations.some(mutation =>
                    (mutation.type === 'childList' && mutation.target === body)
                    || (mutation.type === 'attributes' && mutation.target.parentElement === body)
                );
                if (rowsChanged) { page = 1; render(); }
            });
            observer.observe(body, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
