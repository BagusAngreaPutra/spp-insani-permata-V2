(() => {
    'use strict';

    const tableStates = new WeakMap();
    const tableToolbars = new WeakMap();
    const originalRowOrders = new WeakMap();
    let toolbarNumber = 0;
    const navigateServer = (table, url) => {
        const event = new CustomEvent('permata:server-navigate', { bubbles: true, cancelable: true, detail: { url } });
        if (table.dispatchEvent(event)) location.assign(url);
    };
    const collator = new Intl.Collator('id-ID', {
        numeric: true,
        sensitivity: 'base',
        ignorePunctuation: false,
    });
    const blockedHeaders = /^(?:#|no\.?|nomor|aksi|action|pilih|opsi|menu)$/i;
    const monthNumbers = {
        jan: 1, januari: 1, january: 1,
        feb: 2, februari: 2, february: 2,
        mar: 3, maret: 3, march: 3,
        apr: 4, april: 4,
        mei: 5, may: 5,
        jun: 6, juni: 6, june: 6,
        jul: 7, juli: 7, july: 7,
        agu: 8, agt: 8, agustus: 8, aug: 8, august: 8,
        sep: 9, sept: 9, september: 9,
        okt: 10, oktober: 10, oct: 10, october: 10,
        nov: 11, november: 11,
        des: 12, desember: 12, dec: 12, december: 12,
    };

    const normalize = (value) => String(value ?? '')
        .replace(/\u00a0/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const cellValue = (cell) => {
        if (!cell) return '';
        if (cell.dataset.sortValue !== undefined) return normalize(cell.dataset.sortValue);

        const semanticTime = cell.querySelector('time[datetime]');
        if (semanticTime) return normalize(semanticTime.getAttribute('datetime'));

        return normalize(cell.textContent);
    };

    const numericValue = (raw) => {
        let value = normalize(raw).toLowerCase();
        if (!value || /^(?:-|—|–|n\/a)$/i.test(value)) return null;

        const carriesNumericMeaning = /(?:rp|idr|%)/i.test(value)
            || /^\(?[+-]?[\d\s.,]+\)?$/.test(value);
        if (!carriesNumericMeaning) return null;

        const negative = /^\(.*\)$/.test(value) || value.startsWith('-');
        value = value
            .replace(/[()]/g, '')
            .replace(/(?:rp|idr)/gi, '')
            .replace(/%/g, '')
            .replace(/\s/g, '')
            .replace(/[^\d,.-]/g, '');

        if (!/\d/.test(value)) return null;

        const dotCount = (value.match(/\./g) || []).length;
        const commaCount = (value.match(/,/g) || []).length;

        if (dotCount && commaCount) {
            const decimalSeparator = value.lastIndexOf(',') > value.lastIndexOf('.') ? ',' : '.';
            const thousandsSeparator = decimalSeparator === ',' ? /\./g : /,/g;
            value = value.replace(thousandsSeparator, '');
            if (decimalSeparator === ',') value = value.replace(',', '.');
        } else if (commaCount) {
            value = /^\d{1,3}(?:,\d{3})+$/.test(value)
                ? value.replace(/,/g, '')
                : value.replace(',', '.');
        } else if (dotCount && /^\d{1,3}(?:\.\d{3})+$/.test(value)) {
            value = value.replace(/\./g, '');
        }

        const parsed = Number.parseFloat(value);
        if (!Number.isFinite(parsed)) return null;

        return negative ? -Math.abs(parsed) : parsed;
    };

    const dateValue = (raw) => {
        const value = normalize(raw).toLowerCase().replace(/,/g, '');
        if (!value || /^(?:-|—|–|n\/a)$/i.test(value)) return null;

        let match = value.match(/^(\d{4})[-/]([01]?\d)[-/]([0-3]?\d)(?:[ t](\d{1,2}):?(\d{2})?(?::(\d{2}))?)?$/);
        if (match) {
            const [, year, month, day, hour = 0, minute = 0, second = 0] = match;
            return Date.UTC(+year, +month - 1, +day, +hour, +minute, +second);
        }

        match = value.match(/^([0-3]?\d)[-/.]([01]?\d)[-/.](\d{4})(?:\s+(\d{1,2}):?(\d{2})?(?::(\d{2}))?)?$/);
        if (match) {
            const [, day, month, year, hour = 0, minute = 0, second = 0] = match;
            return Date.UTC(+year, +month - 1, +day, +hour, +minute, +second);
        }

        match = value.match(/^([0-3]?\d)\s+([a-z]+)\s+(\d{4})(?:\s+(\d{1,2}):?(\d{2})?(?::(\d{2}))?)?$/i);
        if (match) {
            const [, day, monthName, year, hour = 0, minute = 0, second = 0] = match;
            const month = monthNumbers[monthName];
            if (month) return Date.UTC(+year, month - 1, +day, +hour, +minute, +second);
        }

        match = value.match(/^([a-z]+)\s+(\d{4})$/i);
        if (match && monthNumbers[match[1]]) {
            return Date.UTC(+match[2], monthNumbers[match[1]] - 1, 1);
        }

        match = value.match(/^(\d{4})\s*[\/-]\s*(\d{4})$/);
        if (match && +match[2] === +match[1] + 1) {
            return Date.UTC(+match[1], 0, 1);
        }

        match = value.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
        if (match) return (+match[1] * 3600) + (+match[2] * 60) + (+(match[3] || 0));

        return null;
    };

    const columnType = (rows, index) => {
        const samples = rows
            .map((row) => cellValue(row.cells[index]))
            .filter((value) => value && !/^(?:-|—|–|n\/a)$/i.test(value))
            .slice(0, 12);

        if (samples.length && samples.every((value) => dateValue(value) !== null)) return 'date';
        if (samples.length && samples.every((value) => numericValue(value) !== null)) return 'number';

        return 'text';
    };

    const comparableValue = (raw, type) => {
        if (type === 'date') return dateValue(raw);
        if (type === 'number') return numericValue(raw);
        return normalize(raw).toLocaleLowerCase('id-ID');
    };

    const sortableRows = (body, columnIndex) => [...body.rows].filter((row) => {
        if (row.matches('[data-sort-fixed], .pi-sort-fixed')) return false;
        if (row.cells.length <= columnIndex) return false;
        if ([...row.cells].some((cell) => cell.colSpan > 1)) return false;
        return true;
    });

    const renumberRows = (table, body, orderedRows) => {
        const headers = table.tHead?.rows[table.tHead.rows.length - 1]?.cells;
        if (!headers) return;

        const numberIndex = [...headers].findIndex((header) => /^(?:#|no\.?|nomor)$/i.test(
            normalize(header.dataset.piSortLabel || header.textContent)
        ));
        if (numberIndex < 0) return;

        const numberedRows = orderedRows.filter((row) => row.cells[numberIndex]);
        if (!numberedRows.length || !numberedRows.every((row) => /^\d+$/.test(normalize(row.cells[numberIndex].textContent)))) return;

        const initialNumbers = numberedRows.map((row) => Number.parseInt(row.cells[numberIndex].textContent, 10));
        const start = Math.min(...initialNumbers);
        numberedRows.forEach((row, index) => {
            row.cells[numberIndex].textContent = String(start + index);
        });
    };

    const updateHeaderState = (table, activeHeader, direction, type) => {
        const headers = table.tHead?.querySelectorAll('th.pi-sortable-column') || [];
        headers.forEach((header) => {
            const active = header === activeHeader;
            header.setAttribute('aria-sort', active ? (direction === 'asc' ? 'ascending' : 'descending') : 'none');

            const description = header.querySelector('.pi-sort-sr');
            if (description) {
                description.textContent = active
                    ? `Diurutkan ${direction === 'asc' ? 'menaik' : 'menurun'}`
                    : 'Belum diurutkan';
            }
        });

        const nextDirection = direction === 'asc' ? 'desc' : 'asc';
        const nextLabel = type === 'date'
            ? (nextDirection === 'asc' ? 'Urutkan waktu terlama' : 'Urutkan waktu terbaru')
            : (nextDirection === 'asc' ? 'Urutkan A ke Z atau terkecil' : 'Urutkan Z ke A atau terbesar');
        if (activeHeader) activeHeader.title = nextLabel;
    };

    const resolveSortTargets = (table, headerRow) => {
        const columns = [...headerRow.cells]
            .map((header, index) => {
                if (!header.classList.contains('pi-sortable-column')) return null;
                const rows = [...table.tBodies].flatMap((body) => sortableRows(body, index));
                return {
                    header,
                    index,
                    label: normalize(header.dataset.piSortLabel || header.textContent),
                    type: columnType(rows, index),
                };
            })
            .filter(Boolean);

        const textColumns = columns.filter(({ type }) => type === 'text');
        const dateColumns = columns.filter(({ type }) => type === 'date');
        const textColumn = textColumns.find(({ label }) =>
            /\b(nama|siswa|sekolah|kelas|guru|admin|jenis|tagihan|pembayaran|barang|produk|kategori|judul)\b/i.test(label)
        ) || textColumns.find(({ label }) => !/\b(status|aktif|tipe)\b/i.test(label)) || textColumns[0] || null;
        const dateColumn = dateColumns.find(({ label }) =>
            /\b(tanggal|waktu|jatuh tempo|dibuat|diperbarui|periode)\b/i.test(label)
        ) || dateColumns[0] || null;

        return { textColumn, dateColumn };
    };

    const updateToolbarState = (table, columnIndex, direction, type) => {
        const toolbar = tableToolbars.get(table);
        if (!toolbar) return;

        const targets = resolveSortTargets(table, toolbar.headerRow);
        toolbar.targets = targets;

        if (type === 'natural') {
            toolbar.select.value = `date-${direction}`;
        } else if (type === 'date' && targets.dateColumn?.index === columnIndex) {
            toolbar.select.value = `date-${direction}`;
        } else if (targets.textColumn?.index === columnIndex) {
            toolbar.select.value = `text-${direction}`;
        } else {
            toolbar.select.value = '';
        }

        toolbar.root.dataset.active = toolbar.select.value ? 'true' : 'false';
    };

    const rememberOriginalRows = (table) => {
        [...table.tBodies].forEach((body) => {
            let nextOrder = 0;
            [...body.rows].forEach((row) => {
                if (originalRowOrders.has(row)) {
                    nextOrder = Math.max(nextOrder, originalRowOrders.get(row) + 1);
                    return;
                }
                originalRowOrders.set(row, nextOrder);
                nextOrder += 1;
            });
        });
    };

    const sortTableByOriginalOrder = (table, direction) => {
        rememberOriginalRows(table);
        const multiplier = direction === 'asc' ? 1 : -1;

        [...table.tBodies].forEach((body) => {
            const rows = [...body.rows].filter((row) => {
                if (row.matches('[data-sort-fixed], .pi-sort-fixed')) return false;
                if ([...row.cells].some((cell) => cell.colSpan > 1)) return false;
                return true;
            });
            if (rows.length < 2) return;

            rows.sort((left, right) => (
                (originalRowOrders.get(left) - originalRowOrders.get(right)) * multiplier
            ));

            const fixedRows = [...body.rows].filter((row) => !rows.includes(row));
            const fragment = document.createDocumentFragment();
            [...rows, ...fixedRows].forEach((row) => fragment.appendChild(row));
            body.appendChild(fragment);
            renumberRows(table, body, rows);
        });

        tableStates.set(table, { columnIndex: -1, direction, type: 'natural' });
        updateHeaderState(table, null, direction, 'date');
        updateToolbarState(table, -1, direction, 'natural');
        table.dispatchEvent(new CustomEvent('permata:table-sorted', {
            detail: { columnIndex: -1, direction, type: 'natural' },
        }));
    };

    const sortTable = (table, header, columnIndex, requestedDirection = null) => {
        const allRows = [...table.tBodies].flatMap((body) => sortableRows(body, columnIndex));
        if (allRows.length < 2) return;

        const type = columnType(allRows, columnIndex);
        const current = tableStates.get(table);
        const direction = requestedDirection || (
            current?.columnIndex === columnIndex
                ? (current.direction === 'asc' ? 'desc' : 'asc')
                : (type === 'date' ? 'desc' : 'asc')
        );
        const multiplier = direction === 'asc' ? 1 : -1;

        [...table.tBodies].forEach((body) => {
            const rows = sortableRows(body, columnIndex);
            if (rows.length < 2) return;

            const decorated = rows.map((row, originalIndex) => {
                const raw = cellValue(row.cells[columnIndex]);
                const value = comparableValue(raw, type);
                return {
                    row,
                    originalIndex,
                    value,
                    empty: raw === '' || value === null,
                };
            });

            decorated.sort((left, right) => {
                if (left.empty !== right.empty) return left.empty ? 1 : -1;
                if (left.empty && right.empty) return left.originalIndex - right.originalIndex;

                let result = 0;
                if (type === 'text') result = collator.compare(left.value, right.value);
                else result = left.value === right.value ? 0 : (left.value < right.value ? -1 : 1);

                return result === 0
                    ? left.originalIndex - right.originalIndex
                    : result * multiplier;
            });

            const orderedRows = decorated.map(({ row }) => row);
            const fixedRows = [...body.rows].filter((row) => !rows.includes(row));
            const fragment = document.createDocumentFragment();
            [...orderedRows, ...fixedRows].forEach((row) => fragment.appendChild(row));
            body.appendChild(fragment);
            renumberRows(table, body, orderedRows);
        });

        tableStates.set(table, { columnIndex, direction, type });
        updateHeaderState(table, header, direction, type);
        updateToolbarState(table, columnIndex, direction, type);
        table.dispatchEvent(new CustomEvent('permata:table-sorted', {
            detail: { columnIndex, direction, type },
        }));
    };

    const shouldSkipTable = (table) => {
        if (table.matches('[data-sort-disabled="true"], [data-sortable="false"]')) return true;
        if (table.matches('.receipt-table, [data-print-table]')) return true;
        if (table.closest('.receipt-paper, .print-kwitansi, .pi-receipt-document, [data-receipt-document]')) return true;
        if (!table.tHead || !table.tBodies.length) return true;
        if (table.matches('.items-table') && table.querySelector('input, select, textarea')) return true;
        return false;
    };

    const shouldSkipHeader = (header) => {
        const label = normalize(header.dataset.sortLabel || header.textContent);
        if (!label || blockedHeaders.test(label)) return true;
        if (header.colSpan > 1) return true;
        if (header.matches('[data-sort-disabled="true"], .no-sort, .pi-no-sort')) return true;
        if (header.querySelector('input, select, textarea, button, a')) return true;
        return false;
    };

    const indicatorMarkup = `
        <span class="pi-sort-indicator" aria-hidden="true">
            <svg viewBox="0 0 14 14" fill="none">
                <path class="pi-sort-up" d="M4.25 5.5 7 2.75 9.75 5.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                <path class="pi-sort-down" d="m4.25 8.5 2.75 2.75L9.75 8.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="pi-sort-sr">Belum diurutkan</span>`;

    const sortbarMarkup = `
        <div class="pi-table-options-heading">
            <strong>Tampilan tabel</strong>
            <button type="button" class="pi-table-options-close" aria-label="Tutup pengaturan">&times;</button>
        </div>
        <label class="pi-table-option">Urutkan
        <select class="pi-table-sortbar-select" aria-label="Urutkan data tabel">
            <option value="">Urutan awal</option>
            <option value="text-asc">A – Z</option>
            <option value="text-desc">Z – A</option>
            <option value="date-desc">Terbaru</option>
            <option value="date-asc">Terlama</option>
        </select></label>`;

    const buildSortToolbar = (table, headerRow) => {
        if (tableToolbars.has(table)) return;

        const sortableHeaders = [...headerRow.cells]
            .map((header, index) => ({ header, index }))
            .filter(({ header }) => header.classList.contains('pi-sortable-column'));
        if (!sortableHeaders.length) return;
        const rows = [...table.tBodies].flatMap(body => sortableRows(body, 0));
        if (rows.length < 2 && !table.hasAttribute('data-server-paginate')) return;

        const existingRoot = document.getElementById(table.dataset.piToolbar || '');
        const root = existingRoot || document.createElement('div');
        root.classList.add('pi-table-sortbar');
        toolbarNumber += 1;
        if (!root.id) root.id = `pi-table-toolbar-${toolbarNumber}`;
        root.setAttribute('role', 'group');
        root.setAttribute('aria-label', 'Kontrol tabel');
        const meta = root.querySelector('.pi-table-meta') || document.createElement('span');
        meta.className = 'pi-table-meta';
        if (!existingRoot) meta.textContent = `${Number(table.dataset.total || rows.length).toLocaleString('id-ID')} data`;
        const button = root.querySelector('.pi-table-options-button') || document.createElement('button');
        button.type = 'button';
        button.className = 'pi-table-options-button';
        button.setAttribute('aria-label', 'Atur tampilan tabel');
        button.title = 'Atur urutan dan tampilan tabel';
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14M7 3v4M13 8v4M8 13v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        if (!existingRoot) root.append(meta, button);

        // A top-layer panel cannot be cut off by horizontally scrolling tables.
        const panel = document.createElement('div');
        panel.id = `pi-table-options-${toolbarNumber}`;
        panel.className = 'pi-table-options-panel';
        panel.setAttribute('popover', 'auto');
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-label', 'Pengaturan tampilan tabel');
        panel.innerHTML = sortbarMarkup;
        document.body.append(panel);
        const lifecycle = new AbortController();
        const listenerOptions = { signal: lifecycle.signal };
        table.addEventListener('permata:table-destroy', () => {
            lifecycle.abort();
            panel.remove();
            tableToolbars.delete(table);
        }, { once: true });
        button.setAttribute('aria-controls', panel.id);
        table.dataset.piToolbar = root.id;
        table.dataset.piOptionsPanel = panel.id;

        const nativePopover = typeof panel.showPopover === 'function';
        if (!nativePopover) { panel.removeAttribute('popover'); panel.hidden = true; }
        const isOpen = () => nativePopover ? panel.matches(':popover-open') : !panel.hidden;
        const positionPanel = () => {
            const trigger = button.getBoundingClientRect();
            const bounds = panel.getBoundingClientRect();
            panel.style.left = `${Math.max(12, Math.min(trigger.right - bounds.width, innerWidth - bounds.width - 12))}px`;
            panel.style.top = `${Math.max(12, trigger.bottom + bounds.height + 8 <= innerHeight - 12 ? trigger.bottom + 8 : trigger.top - bounds.height - 8)}px`;
        };
        const closePanel = () => {
            if (nativePopover) { if (isOpen()) panel.hidePopover(); }
            else panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        };
        button.addEventListener('click', () => {
            if (isOpen()) { closePanel(); return; }
            if (nativePopover) panel.showPopover();
            else panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            positionPanel();
            select.focus({ preventScroll: true });
        }, listenerOptions);
        panel.addEventListener('toggle', () => button.setAttribute('aria-expanded', String(isOpen())), listenerOptions);
        panel.querySelector('.pi-table-options-close').addEventListener('click', () => { closePanel(); button.focus(); });
        window.addEventListener('resize', () => { if (isOpen()) positionPanel(); }, listenerOptions);
        document.addEventListener('scroll', event => {
            if (!isOpen() || event.target === panel || panel.contains(event.target)) return;
            const trigger = button.getBoundingClientRect();
            if (trigger.bottom < 0 || trigger.top > innerHeight) closePanel();
            else positionPanel();
        }, { ...listenerOptions, capture: true });
        if (!nativePopover) {
            document.addEventListener('click', event => { if (!button.contains(event.target) && !panel.contains(event.target)) closePanel(); }, listenerOptions);
        }
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && isOpen()) { closePanel(); button.focus(); } }, listenerOptions);

        const select = panel.querySelector('.pi-table-sortbar-select');
        const targets = resolveSortTargets(table, headerRow);

        select.addEventListener('change', () => {
            if (table.hasAttribute('data-server-paginate')) {
                const url = new URL(location.href);
                const [mode, direction] = select.value.split('-');
                if (select.value) {
                    url.searchParams.set('sort', mode === 'date' ? table.dataset.dateSort : table.dataset.textSort);
                    url.searchParams.set('direction', direction);
                } else { url.searchParams.delete('sort'); url.searchParams.delete('direction'); }
                url.searchParams.delete('page');
                navigateServer(table, url);
                return;
            }
            if (!select.value) {
                sortTableByOriginalOrder(table, 'asc');
                select.value = '';
                root.dataset.active = 'false';
                return;
            }

            const [mode, direction] = select.value.split('-');
            const refreshedTargets = resolveSortTargets(table, headerRow);
            const target = mode === 'date' ? refreshedTargets.dateColumn : refreshedTargets.textColumn;
            if (!target) {
                if (mode === 'date') sortTableByOriginalOrder(table, direction);
                return;
            }

            sortTable(table, target.header, target.index, direction);
        });
        if (table.hasAttribute('data-server-paginate')) {
            const params = new URLSearchParams(location.search);
            const sort = params.get('sort');
            if (sort === table.dataset.textSort) select.value = `text-${params.get('direction') || 'asc'}`;
            else if (sort === table.dataset.dateSort) select.value = `date-${params.get('direction') || 'desc'}`;
        }

        tableToolbars.set(table, { root, select, headerRow, targets });
        if (!existingRoot) table.insertAdjacentElement('beforebegin', root);
        document.dispatchEvent(new CustomEvent('permata:table-tools-ready', { detail: { table } }));
    };

    const enhanceTable = (table) => {
        if (shouldSkipTable(table)) return;
        rememberOriginalRows(table);

        const headerRow = table.tHead.rows[table.tHead.rows.length - 1];
        if (!headerRow) return;

        let enhancedColumns = 0;
        [...headerRow.cells].forEach((header, index) => {
            if (header.dataset.piSortBound === 'true' || shouldSkipHeader(header)) return;
            const serverKey = table.dataset.sortColumns?.split(',')[index];
            if (table.hasAttribute('data-server-paginate') && !serverKey) return;

            header.dataset.piSortBound = 'true';
            header.dataset.piSortLabel = normalize(header.textContent);
            header.classList.add('pi-sortable-column');
            header.setAttribute('tabindex', '0');
            header.setAttribute('aria-sort', 'none');
            header.title = 'Klik untuk mengurutkan';
            header.insertAdjacentHTML('beforeend', indicatorMarkup);

            const activate = () => {
                if (table.hasAttribute('data-server-paginate')) {
                    const url = new URL(location.href);
                    const direction = url.searchParams.get('sort') === serverKey && url.searchParams.get('direction') === 'asc' ? 'desc' : 'asc';
                    url.searchParams.set('sort', serverKey); url.searchParams.set('direction', direction); url.searchParams.delete('page');
                    navigateServer(table, url); return;
                }
                sortTable(table, header, index);
            };
            if (table.hasAttribute('data-server-paginate') && new URLSearchParams(location.search).get('sort') === serverKey) {
                header.setAttribute('aria-sort', new URLSearchParams(location.search).get('direction') === 'desc' ? 'descending' : 'ascending');
            }
            header.addEventListener('click', (event) => {
                if (event.target.closest('a, button, input, select, textarea')) return;
                activate();
            });
            header.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                activate();
            });
            enhancedColumns += 1;
        });

        if (enhancedColumns || table.querySelector('th.pi-sortable-column')) {
            table.dataset.piSortable = 'true';
            buildSortToolbar(table, headerRow);
        }
    };

    const enhanceAllTables = (root = document) => {
        if (root.matches?.('table')) enhanceTable(root);
        root.querySelectorAll?.('table').forEach(enhanceTable);
    };

    const start = () => {
        enhanceAllTables();

        let scheduled = false;
        const observer = new MutationObserver((mutations) => {
            if (!mutations.some((mutation) => mutation.addedNodes.length)) return;
            if (scheduled) return;

            scheduled = true;
            window.requestAnimationFrame(() => {
                enhanceAllTables();
                scheduled = false;
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
