'use strict';

/* Template system */
document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('.main-content');
        if (!root) return;

        const customFilterForms = '.billing-scope-form, .history-filter-form, .overview-filter-form';

        root.querySelectorAll('form').forEach((form) => {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const hints = `${form.id} ${form.className} ${form.parentElement?.className || ''}`;
            const looksLikeFilter = method === 'GET' &&
                form.querySelector('input:not([type="hidden"]), select, textarea') &&
                (/filter|search/i.test(hints) || form.closest('.filter-section, .filter-card, .filter-container'));

            if (!looksLikeFilter || form.matches(customFilterForms)) return;
            form.classList.add('pi-filter-form');

            const layout = Array.from(form.children).find((child) => {
                if (child.matches('.filter-grid, .pi-filter-grid')) return true;

                const fieldChildren = Array.from(child.children).filter((item) =>
                    item.matches('input:not([type="hidden"]), select, textarea') ||
                    item.querySelector('input:not([type="hidden"]), select, textarea')
                );

                return fieldChildren.length > 1;
            });

            if (layout) {
                layout.classList.add('pi-filter-grid');
                Array.from(layout.children).forEach((group) => {
                    if (group.querySelector('input:not([type="hidden"]), select, textarea')) {
                        group.classList.add('pi-filter-field');
                    }
                });
            }

            const controls = Array.from(form.querySelectorAll(
                'button[type="submit"], button.filter-btn, .reset-btn, a.btn-filter-secondary'
            )).filter((control) => control.closest('form') === form);

            controls.forEach((control) => {
                control.classList.add('pi-filter-control');
                const group = control.closest('.form-group');
                if (group && group.closest('form') === form && !group.querySelector('input:not([type="hidden"]), select, textarea')) {
                    group.classList.add('pi-filter-action-group');
                }
            });

            form.querySelectorAll(
                '.filter-actions, .filter-buttons, .overview-filter-buttons, .report-filter-actions'
            ).forEach((actions) => actions.classList.add('pi-filter-actions'));

            const directControls = controls.filter((control) => control.parentElement === form);
            if (directControls.length) {
                const actions = document.createElement('div');
                actions.className = 'filter-actions pi-filter-actions';
                form.insertBefore(actions, directControls[0]);
                directControls.forEach((control) => actions.appendChild(control));
            }
        });

        const iconRules = [
            [/\b(tambah|buat|baru)\b/i, 'fa-plus'],
            [/\b(simpan|save)\b/i, 'fa-floppy-disk'],
            [/\b(edit|ubah|koreksi)\b/i, 'fa-pen'],
            [/\b(hapus|delete)\b/i, 'fa-trash-can'],
            [/\b(lihat|detail|view)\b/i, 'fa-eye'],
            [/\b(kembali|back)\b/i, 'fa-arrow-left'],
            [/\b(batal|batalkan)\b/i, 'fa-xmark'],
            [/\b(cetak|print)\b/i, 'fa-print'],
            [/\bkwitansi\b/i, 'fa-receipt'],
            [/\b(download|export|unduh)\b/i, 'fa-file-arrow-down'],
            [/\b(import|unggah|upload)\b/i, 'fa-file-arrow-up'],
            [/\b(filter|tampilkan)\b/i, 'fa-filter'],
            [/\b(cari|search)\b/i, 'fa-magnifying-glass'],
            [/\b(reset|muat ulang)\b/i, 'fa-rotate-left'],
            [/\b(bayar|pembayaran)\b/i, 'fa-wallet'],
            [/\b(lanjut|proses)\b/i, 'fa-arrow-right'],
            [/\b(luluskan|selesai|konfirmasi)\b/i, 'fa-circle-check']
        ];

        const toneRules = [
            [/\b(hapus|delete|batal|batalkan|nonaktifkan|tolak)\b/i, 'pi-tone-danger'],
            [/\b(edit|ubah|koreksi)\b/i, 'pi-tone-warning'],
            [/\b(simpan|save|update|luluskan|selesai|konfirmasi|aktifkan|setujui)\b/i, 'pi-tone-success'],
            [/\b(cetak|print)\b/i, 'pi-tone-print'],
            [/\b(download|export|unduh)\b/i, 'pi-tone-export'],
            [/\b(import|unggah|upload)\b/i, 'pi-tone-import'],
            [/\b(kembali|back)\b/i, 'pi-tone-back'],
            [/\b(reset|muat ulang|refresh)\b/i, 'pi-tone-secondary'],
            [/\b(bayar|pembayaran|kwitansi)\b/i, 'pi-tone-payment'],
            [/\b(lihat|detail|view|cari|search|data siswa)\b/i, 'pi-tone-info'],
            [/\b(tambah|buat|baru|filter|tampilkan|lanjut|proses|kelola|atur|tetapkan)\b/i, 'pi-tone-primary']
        ];

        const toneClassNames = toneRules.map(([, className]) => className);

        root.querySelectorAll('a, button').forEach((control) => {
            if (control.matches(
                '.app-nav-link, .app-icon-button, .payment-group-title, ' +
                '.billing-search-clear, .transaction-tabs > a, .payment-activation-button, ' +
                '[class*="search-clear"], [data-bs-toggle="collapse"]'
            )) return;
            const textLabel = (control.textContent || '').replace(/\s+/g, ' ').trim();
            const label = (
                textLabel ||
                control.getAttribute('aria-label') ||
                control.getAttribute('title') ||
                ''
            ).replace(/\s+/g, ' ').trim();
            if (!label || label.length > 60) return;

            const tone = toneRules.find(([pattern]) => pattern.test(label));
            if (tone) {
                control.classList.remove(...toneClassNames);
                control.classList.add(tone[1]);
            }

            if (control.querySelector('i, svg, img') || control.classList.contains('pi-auto-icon')) return;
            const match = iconRules.find(([pattern]) => pattern.test(label));
            if (!match) return;
            const icon = document.createElement('i');
            icon.className = `fas ${match[1]}`;
            icon.setAttribute('aria-hidden', 'true');
            control.prepend(icon);
            control.classList.add('pi-auto-icon');
        });

        const tableIconRules = [
            [/^(no|nomor|#)$/i, 'fa-hashtag'],
            [/\b(aksi|action)\b/i, 'fa-sliders'],
            [/status|aktif/i, 'fa-signal'],
            [/tanggal|periode|tahun ajaran|jatuh tempo/i, 'fa-calendar-days'],
            [/sekolah/i, 'fa-school'],
            [/kelas|tingkat/i, 'fa-users-rectangle'],
            [/siswa|nama|admin|petugas/i, 'fa-user'],
            [/username/i, 'fa-at'],
            [/barang|produk|stok/i, 'fa-box'],
            [/kategori|jenis|sumber/i, 'fa-tags'],
            [/nominal|jumlah|total|harga|saldo|sisa|dibayar|pemasukan|pengeluaran/i, 'fa-coins'],
            [/keterangan|deskripsi|alamat/i, 'fa-align-left'],
            [/kode|nis/i, 'fa-barcode']
        ];

        const statusTone = (label) => {
            if (/^(ya|aktif|lunas|sukses|berhasil|aman|tersedia)$/i.test(label)) return 'badge-success';
            if (/^(tidak|nonaktif|belum lunas|belum|gagal|habis|dibatalkan)$/i.test(label)) return 'badge-danger';
            if (/^(belum jatuh tempo|mendatang|menunggu|sebagian|menipis|tertunda)$/i.test(label)) return 'badge-warning';
            if (/^(proses|diproses|berjalan)$/i.test(label)) return 'badge-info';
            return 'badge-secondary';
        };

        const badgeSelector = [
            '.badge', '.status-badge', '.billing-status', '.payment-status',
            '.paid-badge', '.role-badge', '.flow-state',
            '[class^="badge-"]', '[class*=" badge-"]'
        ].join(',');
        const badgeIconRules = [
            [/\b(belum jatuh tempo|mendatang)\b/i, 'fa-clock'],
            [/\b(belum lunas|belum bayar|gagal|habis|dibatalkan|nonaktif|tidak aktif)\b/i, 'fa-circle-exclamation'],
            [/\b(sebagian|menipis)\b/i, 'fa-circle-half-stroke'],
            [/\b(menunggu|tertunda)\b/i, 'fa-hourglass-half'],
            [/\b(lunas|aktif|sukses|berhasil|aman|tersedia|selesai|tercatat)\b/i, 'fa-circle-check'],
            [/\b(proses|diproses|berjalan)\b/i, 'fa-arrows-rotate'],
            [/\b(super admin|admin)\b/i, 'fa-user-shield'],
            [/\bguru\b/i, 'fa-chalkboard-user'],
            [/\bsiswa\b/i, 'fa-user-graduate'],
            [/\b(tahun ajaran|periode)\b/i, 'fa-calendar-days'],
            [/\b(tagihan|pembayaran)\b/i, 'fa-receipt']
        ];
        const semanticStatusClasses = [
            'pi-status-success', 'pi-status-warning', 'pi-status-danger', 'pi-status-info'
        ];
        const semanticStatus = (label) => {
            if (/\b(belum jatuh tempo|mendatang|sebagian|menunggu|tertunda|pending|menipis)\b/i.test(label)) {
                return 'pi-status-warning';
            }
            if (/\b(belum lunas|belum bayar|belum dibayar|menunggak|terlambat|gagal|habis|dibatalkan|ditolak|nonaktif|tidak aktif|tidak valid)\b/i.test(label) || /^(belum|tidak)$/i.test(label)) {
                return 'pi-status-danger';
            }
            if (/\b(lunas|aktif|sukses|berhasil|aman|tersedia|selesai|tercatat|diterima|valid)\b/i.test(label)) {
                return 'pi-status-success';
            }
            if (/\b(proses|diproses|berjalan|informasi)\b/i.test(label)) {
                return 'pi-status-info';
            }
            return null;
        };

        const decorateBadge = (badge) => {
            if (!(badge instanceof HTMLElement) || badge.classList.contains('pi-badge-iconized')) return;

            const label = (badge.textContent || '').replace(/\s+/g, ' ').trim();
            if (!label) return;

            if (/\b(belum jatuh tempo|mendatang)\b/i.test(label)) {
                badge.classList.add('pi-status-upcoming');
            }

            const semanticClass = semanticStatus(label);
            badge.classList.remove(...semanticStatusClasses);
            if (semanticClass) badge.classList.add(semanticClass);

            if (!badge.querySelector('i, svg, img')) {
                const iconRule = badgeIconRules.find(([pattern]) => pattern.test(label));
                const icon = document.createElement('i');
                icon.className = `fas ${iconRule?.[1] || 'fa-tag'} pi-badge-icon`;
                icon.setAttribute('aria-hidden', 'true');
                badge.prepend(icon);
            } else {
                const icon = badge.querySelector('i');
                if (icon) icon.classList.add('pi-badge-icon');
            }

            badge.classList.add('pi-badge-iconized');
        };

        const decorateBadges = (scope) => {
            if (scope.matches?.(badgeSelector)) decorateBadge(scope);
            scope.querySelectorAll?.(badgeSelector).forEach(decorateBadge);
        };

        const responsiveTableSelector = 'table:not(.receipt-table):not(.payment-bill-table)';
        const labelResponsiveTable = (table) => {
            if (!(table instanceof HTMLTableElement) || !table.matches(responsiveTableSelector)) return;

            const headings = Array.from(table.querySelectorAll(':scope > thead > tr:first-child > th'));
            if (!headings.length) return;

            const labels = headings.map((heading) => {
                if (heading.dataset.piSortLabel) return heading.dataset.piSortLabel;

                const cleanHeading = heading.cloneNode(true);
                cleanHeading.querySelectorAll('.pi-sort-indicator, .pi-sort-sr, i, svg').forEach((node) => node.remove());
                return (cleanHeading.textContent || '').replace(/\s+/g, ' ').trim();
            });

            table.classList.add('pi-responsive-table');
            const sections = [...Array.from(table.tBodies), table.tFoot].filter(Boolean);
            sections.forEach((section) => {
                Array.from(section.rows).forEach((row) => {
                    let columnIndex = 0;
                    Array.from(row.cells).forEach((cell) => {
                        const span = Math.max(1, Number(cell.colSpan) || 1);
                        const label = labels[columnIndex] || '';

                        if (span === 1 && label) cell.dataset.label = label;
                        else cell.removeAttribute('data-label');

                        columnIndex += span;
                    });
                });
            });
        };

        const labelResponsiveTables = (scope) => {
            if (!(scope instanceof HTMLElement)) return;

            const nearestTable = scope.closest?.(responsiveTableSelector);
            if (nearestTable && root.contains(nearestTable)) labelResponsiveTable(nearestTable);

            if (scope.matches?.(responsiveTableSelector)) labelResponsiveTable(scope);
            scope.querySelectorAll?.(responsiveTableSelector).forEach(labelResponsiveTable);
        };

        root.querySelectorAll('table:not(.receipt-table)').forEach((table) => {
            if (table.closest('.receipt-paper, .print-kwitansi, .pi-receipt-document, [data-receipt-document]')) return;
            labelResponsiveTable(table);
            const headings = Array.from(table.querySelectorAll(':scope > thead > tr:first-child > th'));
            if (!headings.length) return;

            headings.forEach((heading, columnIndex) => {
                const label = (heading.textContent || '').replace(/\s+/g, ' ').trim();
                if (!label) return;

                const iconRule = tableIconRules.find(([pattern]) => pattern.test(label));
                if (iconRule && !heading.querySelector('i, svg')) {
                    const icon = document.createElement('i');
                    icon.className = `fas ${iconRule[1]}`;
                    icon.setAttribute('aria-hidden', 'true');
                    heading.prepend(icon);
                }

                // The payment editor positions its activation controls separately.
                if (table.matches('.payment-bill-table') && /\b(aksi|action)\b/i.test(label)) return;

                const columnClasses = [];
                if (/^(no|nomor|#)$/i.test(label)) columnClasses.push('pi-index-column');
                if (/\b(aksi|action)\b/i.test(label)) columnClasses.push('pi-action-column');
                if (/status|aktif/i.test(label)) columnClasses.push('pi-status-column');
                if (/nominal|jumlah|total|harga|saldo|sisa|dibayar|pemasukan|pengeluaran/i.test(label)) columnClasses.push('pi-money-column');
                if (/pemasukan|dibayar/i.test(label)) columnClasses.push('pi-income-column');
                if (/pengeluaran|sisa|tunggakan/i.test(label)) columnClasses.push('pi-expense-column');
                if (/saldo/i.test(label)) columnClasses.push('pi-balance-column');

                columnClasses.forEach((className) => heading.classList.add(className));

                const columnCells = [];

                Array.from(table.tBodies).forEach((tbody) => {
                    Array.from(tbody.rows).forEach((row) => {
                        const cell = row.cells[columnIndex];
                        if (!cell || cell.colSpan > 1) return;
                        columnCells.push(cell);
                        columnClasses.forEach((className) => cell.classList.add(className));

                        if (columnClasses.includes('pi-action-column')) {
                            let actions = cell.querySelector('.action-buttons, .billing-row-actions, .koperasi-actions');
                            if (!actions) {
                                const directActions = Array.from(cell.children).filter((child) =>
                                    child.matches('a, button') ||
                                    (child.matches('form') && child.querySelector('button, a'))
                                );
                                if (directActions.length) {
                                    actions = document.createElement('div');
                                    cell.insertBefore(actions, directActions[0]);
                                    directActions.forEach((action) => actions.appendChild(action));
                                }
                            }
                            if (actions) actions.classList.add('pi-table-actions');
                            cell.querySelectorAll('a, button').forEach((action) => {
                                action.classList.add('pi-table-action');
                                const actionLabel = (action.textContent || '').replace(/\s+/g, ' ').trim();
                                if (!actionLabel) action.classList.add('pi-icon-only');
                            });
                        }

                        if (columnClasses.includes('pi-status-column')) {
                            const plainLabel = (cell.textContent || '').replace(/\s+/g, ' ').trim();
                            if (
                                plainLabel && plainLabel !== '-' && plainLabel.length <= 24 &&
                                !cell.querySelector('.badge, .status-badge, .billing-status, .payment-status, a, button, input')
                            ) {
                                const badge = document.createElement('span');
                                badge.className = `badge status-badge ${statusTone(plainLabel)}`;
                                badge.textContent = plainLabel;
                                cell.textContent = '';
                                cell.appendChild(badge);
                            }
                        }
                    });
                });

                if (columnClasses.includes('pi-action-column')) {
                    const cellsWithActions = columnCells.filter((cell) => cell.querySelector('a, button'));
                    const actionCount = Math.max(
                        1,
                        ...cellsWithActions.map((cell) => cell.querySelectorAll('a, button').length)
                    );
                    const sizeClass = `pi-actions-${Math.min(actionCount, 4)}`;
                    [heading, ...columnCells].forEach((element) => element.classList.add(sizeClass));

                    const iconOnly = actionCount === 1 && cellsWithActions.length > 0 &&
                        cellsWithActions.every((cell) =>
                            Array.from(cell.querySelectorAll('a, button')).every((action) =>
                                action.classList.contains('pi-icon-only')
                            )
                        );

                    if (iconOnly) {
                        [heading, ...columnCells].forEach((element) =>
                            element.classList.add('pi-actions-icon-only')
                        );
                    }
                }
            });
        });

        decorateBadges(root);

        const badgeObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof HTMLElement) {
                        decorateBadges(node);
                        labelResponsiveTables(node);
                    }
                });
            });
        });
        badgeObserver.observe(root, { childList: true, subtree: true });
    });

/* Print document system */
document.addEventListener('DOMContentLoaded', () => {
        const receiptRoots = document.querySelectorAll(
            '.receipt-paper, .print-kwitansi, .pi-receipt-document, [data-receipt-document]'
        );

        receiptRoots.forEach((root) => {
            root.classList.add('pi-print-document', 'pi-receipt-document');
            root.querySelectorAll('table').forEach((table) => {
                table.dataset.sortDisabled = 'true';
                table.dataset.sortable = 'false';

                const sortbar = table.previousElementSibling;
                if (sortbar?.classList.contains('pi-table-sortbar')) sortbar.remove();

                table.querySelectorAll('.pi-sort-indicator, .pi-sort-sr').forEach((item) => item.remove());
                table.querySelectorAll('thead th').forEach((header) => {
                    header.classList.remove('pi-sortable-column');
                    header.removeAttribute('tabindex');
                    header.removeAttribute('aria-sort');
                    header.removeAttribute('title');
                    header.querySelectorAll(':scope > i').forEach((icon) => icon.remove());
                });
            });
        });
    });

/* Report system */
(() => {
        const markReportPages = () => {
            document.querySelectorAll('.main-content').forEach((page) => {
                if (!page.querySelector('.kop-laporan, .print-title, .print-footer')) return;
                page.classList.add('pi-report-page');
                page.querySelector('.content-area')?.classList.add('pi-report-document');

                page.querySelectorAll('table').forEach((table) => {
                    const labels = Array.from(table.querySelectorAll('thead th'))
                        .map((header) => header.textContent.trim());

                    table.querySelectorAll('tbody tr, tfoot tr').forEach((row) => {
                        Array.from(row.children).forEach((cell, index) => {
                            if (!cell.matches('th, td') || cell.hasAttribute('colspan')) return;
                            if (!cell.dataset.label && labels[index]) {
                                cell.dataset.label = labels[index];
                            }
                        });
                    });
                });
            });
        };

        markReportPages();
        document.addEventListener('DOMContentLoaded', markReportPages);
    })();

/* Dependent class filters */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-class-filter-for]').forEach(function (classSelect) {
        if (classSelect.dataset.classFilterReady === 'true') return;

        const schoolSelect = document.getElementById(classSelect.dataset.classFilterFor);
        if (!schoolSelect) return;

        const allLabel = classSelect.dataset.allLabel || 'Semua kelas';
        const initialClassId = String(classSelect.value || '');
        const classOptions = Array.from(classSelect.options)
            .filter(function (option) { return option.value !== ''; })
            .map(function (option) {
                return {
                    value: String(option.value),
                    schoolId: String(option.dataset.schoolId || ''),
                    schoolName: String(option.dataset.schoolName || ''),
                    label: String(option.dataset.classLabel || option.textContent || '').trim(),
                };
            });

        const createOption = function (value, label, selected) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            option.selected = Boolean(selected);
            return option;
        };

        const renderClasses = function (preserveSelection) {
            const schoolId = String(schoolSelect.value || '');
            const requestedClassId = preserveSelection ? initialClassId : '';
            const availableClasses = classOptions.filter(function (item) {
                return !schoolId || item.schoolId === schoolId;
            });
            const selectionIsValid = availableClasses.some(function (item) {
                return item.value === requestedClassId;
            });

            classSelect.replaceChildren(createOption('', allLabel, !selectionIsValid));

            availableClasses.forEach(function (item) {
                const optionLabel = schoolId || !item.schoolName
                    ? item.label
                    : `${item.schoolName} · ${item.label}`;
                classSelect.appendChild(createOption(item.value, optionLabel, item.value === requestedClassId));
            });

            if (availableClasses.length === 0) {
                const emptyLabel = schoolId
                    ? 'Belum ada kelas pada sekolah ini'
                    : 'Belum ada data kelas';
                classSelect.replaceChildren(createOption('', emptyLabel, true));
                classSelect.disabled = true;
            } else {
                classSelect.disabled = false;
            }

            classSelect.dataset.classFilterReady = 'true';
        };

        schoolSelect.addEventListener('change', function () {
            renderClasses(false);
        });

        renderClasses(true);
    });
});

/* Rupiah inputs */
document.addEventListener('DOMContentLoaded', function () {
    const explicitMoneyNames = new Set([
        'nominal',
        'nominal_spp',
        'penghasilan_ayah',
        'penghasilan_ibu',
        'harga_beli',
        'harga_jual',
        'jumlah_bayar'
    ]);

    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    function formatRupiah(rawValue) {
        return rupiahFormatter.format(Number(rawValue)).replace(/\s+/g, '');
    }

    const normalizeValue = function (value) {
        const text = String(value ?? '').trim();
        if (!text) return '';
        if (/^\d+(?:\.\d+)?$/.test(text) && !text.includes('Rp')) {
            return String(Math.max(0, Math.round(Number(text))));
        }
        return text.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
    };

    const isMoneyInput = function (input) {
        if (!(input instanceof HTMLInputElement) || input.type === 'hidden') return false;
        if (input.hasAttribute('data-rupiah')) return true;

        const name = input.name || '';
        return explicitMoneyNames.has(name) || name.startsWith('pembayaran[');
    };

    const setRawValue = function (input) {
        const rawValue = normalizeValue(input.dataset.rupiahRaw ?? input.value);
        input.dataset.rupiahRaw = rawValue;
        input.type = input.dataset.rupiahOriginalType || 'text';
        input.value = rawValue;
    };

    const setFormattedValue = function (input, force = false) {
        if (!force && document.activeElement === input && !input.readOnly) return;
        const rawValue = normalizeValue(input.dataset.rupiahRaw ?? input.value);
        input.dataset.rupiahRaw = rawValue;
        input.type = 'text';
        input.value = rawValue === '' ? '' : formatRupiah(rawValue);
    };

    const initializeInput = function (input) {
        if (!isMoneyInput(input) || input.dataset.rupiahReady === 'true') return;

        input.dataset.rupiahReady = 'true';
        input.dataset.rupiahOriginalType = input.type;
        input.dataset.rupiahRaw = normalizeValue(input.value);
        input.classList.add('pi-rupiah-input');
        input.inputMode = 'numeric';

        input.addEventListener('focus', function () {
            if (input.readOnly) return;
            setRawValue(input);
            requestAnimationFrame(function () {
                if (document.activeElement === input) input.select();
            });
        });

        input.addEventListener('input', function () {
            input.dataset.rupiahRaw = normalizeValue(input.value);
        });

        input.addEventListener('blur', function () {
            setFormattedValue(input, true);
        });

        setFormattedValue(input);
    };

    const initializeWithin = function (root) {
        if (root instanceof HTMLInputElement) initializeInput(root);
        root.querySelectorAll?.('input').forEach(initializeInput);
    };

    initializeWithin(document);

    document.addEventListener('focusout', function (event) {
        const input = event.target;
        if (input instanceof HTMLInputElement && input.classList.contains('pi-rupiah-input')) {
            setFormattedValue(input, true);
        }
    }, true);

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node instanceof Element) initializeWithin(node);
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const inputs = Array.from(form.querySelectorAll('input.pi-rupiah-input'));
        inputs.forEach(setRawValue);

        setTimeout(function () {
            if (!document.documentElement.contains(form)) return;
            inputs.forEach(setFormattedValue);
        }, 0);
    }, true);

    window.PermataRupiah = {
        format: function (value) {
            const rawValue = normalizeValue(value);
            return rawValue === '' ? '' : formatRupiah(rawValue);
        },
        raw: normalizeValue,
        refresh: function (input) {
            if (!(input instanceof HTMLInputElement)) return;
            input.dataset.rupiahRaw = normalizeValue(input.value);
            setFormattedValue(input);
        }
    };
});
