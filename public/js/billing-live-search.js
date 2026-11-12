/* Live server search: keep the input stable and replace only matching data. */
(() => {
    'use strict';
    const start = () => {
        const form = document.getElementById('billingScopeForm');
        const input = document.getElementById('billingStudentSearch');
        const results = document.getElementById('billingResults');
        if (!form || !input || !results || new URLSearchParams(location.search).get('print') === '1') return;
        const stats = document.getElementById('billingStats');
        const toolbar = document.getElementById('billing-table-toolbar');
        const clear = document.getElementById('billingSearchClear');
        const status = document.getElementById('billingSearchStatus');
        const icon = form.querySelector('.billing-search > i');
        let requestedUrl = new URL(location.href);
        let timer, controller, revision = 0;

        const busy = value => {
            results.setAttribute('aria-busy', String(value));
            stats.setAttribute('aria-busy', String(value));
            results.inert = value;
            results.style.opacity = value ? '0.55' : '';
            icon.className = value ? 'fas fa-spinner fa-spin' : 'fas fa-magnifying-glass';
        };
        const clearState = () => clear.classList.toggle('is-visible', input.value.length > 0);
        const searchUrl = () => {
            const url = new URL(requestedUrl);
            const query = input.value.trim();
            if (query) url.searchParams.set('table_search', query);
            else url.searchParams.delete('table_search');
            url.searchParams.delete('page');
            url.searchParams.delete('print');
            return url;
        };
        const load = (url, { delay = 0, historyMode = 'replace', syncFilters = false } = {}) => {
            requestedUrl = new URL(url);
            clearTimeout(timer);
            controller?.abort();
            const version = ++revision;
            busy(true);
            status.className = 'visually-hidden';
            status.hidden = false;
            status.textContent = 'Mencari siswa…';
            timer = setTimeout(async () => {
                controller = new AbortController();
                const requestController = controller;
                const timeout = setTimeout(() => requestController.abort(), 15000);
                try {
                    const response = await fetch(url, {
                        signal: requestController.signal,
                        headers: { 'X-Billing-Live': '1', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin', cache: 'no-store',
                    });
                    if (!response.ok || response.redirected) throw new Error('Hasil tidak tersedia');
                    const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                    if (version !== revision) return;
                    const nextResults = doc.getElementById('billingResults');
                    const nextStats = doc.getElementById('billingStats');
                    const nextToolbar = doc.getElementById('billing-table-toolbar');
                    if (!nextResults || !nextStats || !nextToolbar) throw new Error('Respons tidak lengkap');
                    // Dispose old popovers/listeners before the table observer binds the new one.
                    results.querySelectorAll('table').forEach(table => table.dispatchEvent(new Event('permata:table-destroy')));
                    toolbar.replaceChildren(...nextToolbar.childNodes);
                    toolbar.removeAttribute('data-active');
                    results.replaceChildren(...nextResults.childNodes);
                    stats.replaceChildren(...nextStats.childNodes);
                    const context = document.querySelector('.billing-panel-heading .billing-panel-context');
                    context.textContent = doc.querySelector('.billing-panel-heading .billing-panel-context').textContent;
                    if (syncFilters) {
                        const nextForm = doc.getElementById(form.id);
                        form.querySelectorAll('select').forEach(field => {
                            field.value = nextForm.elements.namedItem(field.name)?.value ?? '';
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                        input.value = nextForm.elements.namedItem('table_search').value;
                        clearState();
                    }
                    ['per_page', 'sort', 'direction'].forEach(name => {
                        let field = form.querySelector(`input[name="${name}"]`);
                        if (!url.searchParams.has(name)) { field?.remove(); return; }
                        if (!field) { field = document.createElement('input'); field.type = 'hidden'; field.name = name; form.append(field); }
                        field.value = url.searchParams.get(name);
                    });
                    if (historyMode !== 'none') history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url);
                    status.textContent = `${toolbar.querySelector('.pi-table-meta')?.textContent || '0 data'} sesuai pencarian.`;
                } catch (error) {
                    if (version !== revision) return;
                    status.className = 'billing-live-search-error';
                    status.textContent = 'Pencarian gagal dimuat. Hasil sebelumnya masih ditampilkan. Tekan Enter atau tombol Cari untuk mencoba lagi.';
                } finally {
                    clearTimeout(timeout);
                    if (version === revision) busy(false);
                }
            }, delay);
        };
        input.addEventListener('input', event => {
            clearState();
            if (!event.isComposing) load(searchUrl(), { delay: 180 });
        });
        input.addEventListener('compositionend', () => load(searchUrl(), { delay: 180 }));
        input.addEventListener('compositionstart', () => {
            clearTimeout(timer); controller?.abort(); revision++;
            busy(false);
        });
        clear.addEventListener('click', () => {
            input.value = '';
            clearState();
            input.focus({ preventScroll: true });
            load(searchUrl());
        });
        form.addEventListener('submit', event => {
            event.preventDefault();
            const apply = event.submitter?.matches('.pi-compact-apply');
            const url = apply ? new URL(form.action) : searchUrl();
            if (apply) {
                new FormData(form).forEach((value, name) => { if (value !== '') url.searchParams.set(name, value); });
                url.searchParams.delete('page'); url.searchParams.delete('print');
                document.getElementById('billing-filter-panel').querySelector('.pi-compact-close').click();
            }
            load(url, { historyMode: apply ? 'push' : 'replace', syncFilters: apply });
        });
        results.addEventListener('click', event => {
            const link = event.target.closest('[data-server-pagination] a');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            load(new URL(link.href), { historyMode: 'push' });
        });
        results.addEventListener('permata:server-navigate', event => {
            event.preventDefault();
            load(event.detail.url, { historyMode: 'push' });
        });
        window.addEventListener('popstate', () => load(new URL(location.href), { historyMode: 'none', syncFilters: true }));
        clearState();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
