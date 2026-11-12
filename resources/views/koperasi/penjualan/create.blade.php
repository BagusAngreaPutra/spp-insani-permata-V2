@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@php
    $editing = isset($penjualan);
    $defaultItems = $editing ? $penjualan->details->map(fn ($detail) => ['koperasi_id' => $detail->koperasi_id, 'jumlah' => $detail->jumlah])->all() : array_fill(0, 3, []);
    $formItems = old('items', $defaultItems);
@endphp
@push('page-styles')
<style>
    .main-content {
        margin-left: 280px;
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        position: absolute;
        right: 0;
        top: 0;
        width: calc(100% - 280px);
    }
    @media (max-width: 768px) {
        .main-content { margin-left: 0; width: 100%; position: relative; }
    }
    .content-area { padding: 3rem 2.5rem; }
    .form-card {
        background: rgba(255,255,255,0.95);
        border: 1px solid rgba(34,197,94,0.12);
        border-radius: 22px;
        box-shadow: 0 18px 38px rgba(15,23,42,0.09);
        padding: 2rem;
    }
    .page-title { color: #14532d; font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; }
    .form-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .student-tools {
        background: #f8fafc;
        border: 1px dashed #bbf7d0;
        border-radius: 16px;
        margin-bottom: 1rem;
        padding: 1rem;
    }
    .student-tools-title {
        color: #166534;
        font-size: 0.84rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
    }
    .student-filter-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .student-combobox { position: relative; }
    .student-search-input {
        border: 2px solid rgba(34,197,94,0.18);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        width: 100%;
    }
    .student-suggestions {
        background: #fff;
        border: 1px solid #bbf7d0;
        border-radius: 14px;
        box-shadow: 0 18px 36px rgba(15,23,42,0.12);
        display: none;
        left: 0;
        max-height: 280px;
        overflow-y: auto;
        position: absolute;
        right: 0;
        top: calc(100% + 0.4rem);
        z-index: 20;
    }
    .student-suggestions.is-open { display: block; }
    .student-suggestion {
        background: transparent;
        border: none;
        border-bottom: 1px solid #f0fdf4;
        color: #14532d;
        cursor: pointer;
        display: block;
        padding: 0.8rem 0.95rem;
        text-align: left;
        width: 100%;
    }
    .student-suggestion:hover, .student-suggestion.is-active { background: #f0fdf4; }
    .student-suggestion strong { display: block; font-size: 0.95rem; }
    .student-suggestion small { color: #64748b; display: block; margin-top: 0.2rem; }
    .student-empty { color: #64748b; padding: 0.9rem; }
    .form-group { display: flex; flex-direction: column; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #166534; font-weight: 700; margin-bottom: 0.45rem; }
    .form-group input, .form-group select, .form-group textarea {
        border: 2px solid rgba(34,197,94,0.18);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
    }
    .form-group small { color: #dc2626; margin-top: 0.35rem; }
    .items-table { border-collapse: collapse; margin-top: 1rem; width: 100%; }
    .items-table th, .items-table td { border-bottom: 1px solid #dcfce7; padding: 0.75rem; text-align: left; vertical-align: top; }
    .items-table th { color: #166534; font-size: 0.82rem; text-transform: uppercase; }
    .items-table select, .items-table input {
        border: 2px solid rgba(34,197,94,0.18);
        border-radius: 10px;
        padding: 0.65rem;
        width: 100%;
    }
    .total-box {
        align-items: center;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        color: #14532d;
        display: flex;
        font-size: 1.1rem;
        font-weight: 800;
        justify-content: space-between;
        margin-top: 1rem;
        padding: 1rem 1.25rem;
    }
    .btn-add, .btn-remove, .btn-submit, .btn-cancel {
        border: none;
        border-radius: 12px;
        color: #fff;
        font-weight: 800;
        padding: 0.75rem 1rem;
        text-decoration: none;
    }
    .btn-add { background: linear-gradient(135deg, #25845d, #1d6b4c); margin-top: 1rem; }
    .btn-remove { background: linear-gradient(135deg, #ef4444, #dc2626); padding: 0.55rem 0.8rem; }
    .btn-submit { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .btn-cancel { background: linear-gradient(135deg, #64748b, #475569); }
    .form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
    .alert-error { background: #fee2e2; border-radius: 12px; color: #991b1b; margin-bottom: 1rem; padding: 1rem; }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .student-filter-grid { grid-template-columns: 1fr; }
        .items-table { display: block; overflow-x: auto; }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        @include('partials.admin-page-context', [
            'section' => 'Koperasi',
            'current' => 'Transaksi Baru',
            'title' => 'Catat transaksi penjualan koperasi.',
            'description' => 'Pilih siswa pembeli, masukkan barang yang dibeli, lalu simpan agar stok berkurang otomatis.'
        ])

        <div class="form-card">
            <h2 class="page-title"><i class="fas fa-cash-register"></i> {{ $editing ? 'Koreksi '.$penjualan->kode_transaksi : 'Transaksi Penjualan Baru' }}</h2>

            @if($errors->any())
                <div class="alert-error">
                    Periksa kembali transaksi yang dimasukkan.
                    @foreach($errors->all() as $error)<br>{{ $error }}@endforeach
                </div>
            @endif

            <form action="{{ $editing ? route('koperasi.penjualan.update', $penjualan->id) : route('koperasi.penjualan.store') }}" method="POST" id="posForm">
                @csrf
                @if($editing) @method('PUT') @endif

                <div class="student-tools">
                    <div class="student-tools-title">Filter bantu pencarian siswa, opsional</div>
                    <div class="student-filter-grid">
                    <div class="form-group">
                        <label for="filter_sekolah">Filter Sekolah</label>
                        <select id="filter_sekolah">
                            <option value="">Semua Sekolah</option>
                            @foreach($siswa->pluck('sekolah')->filter()->unique('id')->sortBy('nama_sekolah') as $sekolahItem)
                                <option value="{{ $sekolahItem->id }}">{{ $sekolahItem->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filter_kelas">Filter Kelas</label>
                        <select id="filter_kelas">
                            <option value="">Semua Kelas</option>
                            @foreach($siswa->pluck('kelas')->filter()->unique('id')->sortBy('tingkat')->sortBy('nama_kelas') as $kelasItem)
                                <option value="{{ $kelasItem->id }}">{{ $kelasItem->kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_siswa">Urutkan</label>
                        <select id="sort_siswa">
                            <option value="nama">Nama Siswa</option>
                            <option value="nis">NIS</option>
                            <option value="kelas">Kelas</option>
                            <option value="sekolah">Sekolah</option>
                        </select>
                    </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="siswa_id">Siswa Pembeli</label>
                        <div class="student-combobox">
                            <input type="text" id="siswa_search" class="student-search-input" autocomplete="off" placeholder="Ketik nama atau NIS siswa..." value="">
                            <div id="siswa_suggestions" class="student-suggestions"></div>
                        </div>
                        <select name="siswa_id" id="siswa_id" style="display:none;">
                            <option value="">Pilih Siswa</option>
                            @foreach($siswa as $item)
                                <option value="{{ $item->id }}"
                                    data-sekolah="{{ $item->id_sekolah }}"
                                    data-sekolah-nama="{{ $item->sekolah->nama_sekolah ?? '' }}"
                                    data-kelas="{{ $item->kelas_id }}"
                                    data-kelas-nama="{{ $item->kelas->kelas ?? '' }}"
                                    data-nama="{{ $item->nama }}"
                                    data-nis="{{ $item->nis }}"
                                    data-search="{{ strtolower($item->nama . ' ' . $item->nis . ' ' . ($item->kelas->kelas ?? '') . ' ' . ($item->sekolah->nama_sekolah ?? '')) }}"
                                    {{ old('siswa_id', $penjualan->siswa_id ?? null) == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama }} - {{ $item->nis }} ({{ $item->kelas->kelas ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        @error('siswa_id')<small>{{ $message }}</small>@enderror
                        <small id="selected_siswa_text" style="color:#166534; display:none;"></small>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', isset($penjualan) ? $penjualan->tanggal->format('Y-m-d') : date('Y-m-d')) }}" required>
                        @error('tanggal')<small>{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group full">
                        <label for="metode_bayar">Metode pembayaran</label>
                        <select id="metode_bayar" name="metode_bayar" required>
                            <option value="">Pilih metode</option>
                            <option value="tunai" @selected(old('metode_bayar', $penjualan->metode_bayar ?? '') === 'tunai')>Tunai (TN)</option>
                            <option value="transfer" @selected(old('metode_bayar', $penjualan->metode_bayar ?? '') === 'transfer')>Transfer (TF)</option>
                        </select>
                    </div>
                    @if($editing)
                    <div class="form-group full">
                        <label for="alasan_koreksi">Alasan koreksi</label>
                        <textarea name="alasan_koreksi" id="alasan_koreksi" minlength="5" maxlength="1000" required>{{ old('alasan_koreksi') }}</textarea>
                    </div>
                    @endif
                    <div class="form-group full">
                        <label for="catatan">Catatan</label>
                        <textarea name="catatan" id="catatan" rows="2" placeholder="Opsional">{{ old('catatan', $penjualan->catatan ?? '') }}</textarea>
                        @error('catatan')<small>{{ $message }}</small>@enderror
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 46%;">Barang</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th style="width: 110px;">Jumlah</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @foreach($formItems as $i => $formItem)
                            <tr class="item-row">
                                <td>
                                    <select name="items[{{ $i }}][koperasi_id]" class="barang-select">
                                        <option value="">Pilih Barang</option>
                                        @foreach($barang as $item)
                                            <option value="{{ $item->id }}"
                                                data-harga="{{ (float) $item->harga_jual }}"
                                                data-stok="{{ $item->stok }}"
                                                {{ ($formItem['koperasi_id'] ?? null) == $item->id ? 'selected' : '' }}>
                                                {{ $item->nama_barang }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.$i.koperasi_id")<small>{{ $message }}</small>@enderror
                                </td>
                                <td class="stok-cell">-</td>
                                <td class="harga-cell">Rp 0</td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][jumlah]" class="jumlah-input" min="1" value="{{ $formItem['jumlah'] ?? '' }}">
                                    @error("items.$i.jumlah")<small>{{ $message }}</small>@enderror
                                </td>
                                <td class="subtotal-cell">Rp 0</td>
                                <td><button type="button" class="btn-remove">Hapus</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button type="button" class="btn-add" id="addRow"><i class="fas fa-plus"></i> Tambah Baris</button>

                <div class="total-box">
                    <span>Total Transaksi</span>
                    <span id="grandTotal">Rp 0</span>
                </div>

                <div class="form-actions">
                    <a href="{{ route('koperasi.penjualan.index') }}" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit">{{ $editing ? 'Simpan Koreksi' : 'Simpan Transaksi' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const formatter = new Intl.NumberFormat('id-ID');
    const siswaSelect = document.getElementById('siswa_id');
    const siswaSearch = document.getElementById('siswa_search');
    const siswaSuggestions = document.getElementById('siswa_suggestions');
    const selectedSiswaText = document.getElementById('selected_siswa_text');
    const filterSekolah = document.getElementById('filter_sekolah');
    const filterKelas = document.getElementById('filter_kelas');
    const sortSiswa = document.getElementById('sort_siswa');
    const itemsBody = document.getElementById('itemsBody');
    const addRowButton = document.getElementById('addRow');
    const siswaPlaceholder = siswaSelect.querySelector('option[value=""]');
    const siswaOptions = Array.from(siswaSelect.querySelectorAll('option[value]:not([value=""])'));
    const barangOptionsTemplate = Array.from(document.querySelector('.barang-select').options)
        .map(option => option.cloneNode(true));
    let activeSuggestionIndex = -1;
    let visibleSiswaOptions = [];

    function rupiah(value) {
        return 'Rp ' + formatter.format(value || 0);
    }

    function refreshRow(row) {
        const select = row.querySelector('.barang-select');
        const qtyInput = row.querySelector('.jumlah-input');
        const option = select.options[select.selectedIndex];
        const harga = option && option.value ? Number(option.dataset.harga || 0) : 0;
        const stok = option && option.value ? Number(option.dataset.stok || 0) : 0;
        const qty = Number(qtyInput.value || 0);

        row.querySelector('.stok-cell').textContent = option && option.value ? stok : '-';
        row.querySelector('.harga-cell').textContent = rupiah(harga);
        row.querySelector('.subtotal-cell').textContent = rupiah(harga * qty);
    }

    function refreshTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const select = row.querySelector('.barang-select');
            const qtyInput = row.querySelector('.jumlah-input');
            const option = select.options[select.selectedIndex];
            const harga = option && option.value ? Number(option.dataset.harga || 0) : 0;
            total += harga * Number(qtyInput.value || 0);
            refreshRow(row);
        });
        document.getElementById('grandTotal').textContent = rupiah(total);
    }

    function refreshBarangOptions() {
        document.querySelectorAll('.barang-select').forEach(select => {
            const selectedValue = select.value;

            select.innerHTML = '';
            barangOptionsTemplate.forEach(option => select.appendChild(option.cloneNode(true)));

            const stillAvailable = Array.from(select.options).some(option => option.value === selectedValue);
            select.value = stillAvailable ? selectedValue : '';
        });
        refreshTotal();
    }

    function optionText(option) {
        const nama = option.dataset.nama || '';
        const nis = option.dataset.nis || '';
        const kelas = option.dataset.kelasNama || '-';
        const sekolah = option.dataset.sekolahNama || '-';

        return `${nama} - ${nis} (${kelas}) | ${sekolah}`;
    }

    function compareOptions(a, b) {
        const mode = sortSiswa.value;
        const sortKeys = {
            nama: 'nama',
            nis: 'nis',
            kelas: 'kelasNama',
            sekolah: 'sekolahNama'
        };
        const key = sortKeys[mode] || 'nama';
        const valueA = (a.dataset[key] || a.dataset.nama || '').toLowerCase();
        const valueB = (b.dataset[key] || b.dataset.nama || '').toLowerCase();

        return valueA.localeCompare(valueB, 'id', { numeric: true });
    }

    function filterSiswaOptions() {
        const previousStudent = siswaSelect.value;
        const keyword = siswaSearch.value.trim().toLowerCase();
        const sekolahId = filterSekolah.value;
        const kelasId = filterKelas.value;
        visibleSiswaOptions = siswaOptions
            .filter(option => {
                const matchesSearch = !keyword || (option.dataset.search || '').includes(keyword);
                const matchesSekolah = !sekolahId || option.dataset.sekolah === sekolahId;
                const matchesKelas = !kelasId || option.dataset.kelas === kelasId;

                return matchesSearch && matchesSekolah && matchesKelas;
            })
            .sort(compareOptions);

        siswaSelect.innerHTML = '';
        siswaSelect.appendChild(siswaPlaceholder);

        visibleSiswaOptions.forEach(option => {
            option.textContent = optionText(option);
            siswaSelect.appendChild(option);
        });
        siswaSelect.value = previousStudent;

        const stillVisible = visibleSiswaOptions.some(option => option.value === siswaSelect.value);
        if (!stillVisible) {
            siswaSelect.value = '';
            selectedSiswaText.style.display = 'none';
        }

        renderSiswaSuggestions();
        refreshBarangOptions();
    }

    function renderSiswaSuggestions() {
        siswaSuggestions.innerHTML = '';
        activeSuggestionIndex = -1;

        const limitedOptions = visibleSiswaOptions.slice(0, 10);

        if (limitedOptions.length === 0) {
            siswaSuggestions.innerHTML = '<div class="student-empty">Tidak ada siswa yang cocok.</div>';
            return;
        }

        limitedOptions.forEach((option, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'student-suggestion';
            button.dataset.index = index;
            button.innerHTML = `<strong>${option.dataset.nama} - ${option.dataset.nis}</strong><small>${option.dataset.kelasNama || '-'} | ${option.dataset.sekolahNama || '-'}</small>`;
            button.addEventListener('mousedown', event => {
                event.preventDefault();
                selectSiswaOption(option);
            });
            siswaSuggestions.appendChild(button);
        });
    }

    function openSiswaSuggestions() {
        filterSiswaOptions();
        siswaSuggestions.classList.add('is-open');
    }

    function closeSiswaSuggestions() {
        siswaSuggestions.classList.remove('is-open');
        activeSuggestionIndex = -1;
    }

    function selectSiswaOption(option) {
        siswaSelect.value = option.value;
        siswaSearch.value = `${option.dataset.nama} - ${option.dataset.nis}`;
        selectedSiswaText.textContent = `${option.dataset.kelasNama || '-'} | ${option.dataset.sekolahNama || '-'}`;
        selectedSiswaText.style.display = 'block';
        closeSiswaSuggestions();
        refreshBarangOptions();
    }

    function highlightSuggestion(index) {
        const buttons = Array.from(siswaSuggestions.querySelectorAll('.student-suggestion'));
        buttons.forEach(button => button.classList.remove('is-active'));

        if (buttons[index]) {
            buttons[index].classList.add('is-active');
            buttons[index].scrollIntoView({ block: 'nearest' });
        }
    }

    function reindexRows() {
        document.querySelectorAll('.item-row').forEach((row, index) => {
            row.querySelector('.barang-select').name = `items[${index}][koperasi_id]`;
            row.querySelector('.jumlah-input').name = `items[${index}][jumlah]`;
        });
    }

    itemsBody.addEventListener('change', event => {
        if (event.target.classList.contains('barang-select') || event.target.classList.contains('jumlah-input')) {
            refreshTotal();
        }
    });

    itemsBody.addEventListener('input', event => {
        if (event.target.classList.contains('jumlah-input')) {
            refreshTotal();
        }
    });

    itemsBody.addEventListener('click', event => {
        if (!event.target.classList.contains('btn-remove')) {
            return;
        }

        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            event.target.closest('.item-row').remove();
            reindexRows();
            refreshTotal();
        }
    });

    addRowButton.addEventListener('click', () => {
        const clone = document.querySelector('.item-row').cloneNode(true);
        clone.querySelector('.barang-select').value = '';
        clone.querySelector('.jumlah-input').value = '';
        itemsBody.appendChild(clone);
        reindexRows();
        refreshBarangOptions();
    });

    siswaSearch.addEventListener('focus', openSiswaSuggestions);
    siswaSearch.addEventListener('input', () => {
        siswaSelect.value = '';
        selectedSiswaText.style.display = 'none';
        openSiswaSuggestions();
    });
    siswaSearch.addEventListener('blur', () => {
        setTimeout(closeSiswaSuggestions, 120);
    });
    siswaSearch.addEventListener('keydown', event => {
        const buttons = Array.from(siswaSuggestions.querySelectorAll('.student-suggestion'));

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeSuggestionIndex = Math.min(activeSuggestionIndex + 1, buttons.length - 1);
            highlightSuggestion(activeSuggestionIndex);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeSuggestionIndex = Math.max(activeSuggestionIndex - 1, 0);
            highlightSuggestion(activeSuggestionIndex);
        } else if (event.key === 'Enter' && activeSuggestionIndex >= 0 && visibleSiswaOptions[activeSuggestionIndex]) {
            event.preventDefault();
            selectSiswaOption(visibleSiswaOptions[activeSuggestionIndex]);
        } else if (event.key === 'Escape') {
            closeSiswaSuggestions();
        }
    });
    filterSekolah.addEventListener('change', filterSiswaOptions);
    filterKelas.addEventListener('change', filterSiswaOptions);
    sortSiswa.addEventListener('change', filterSiswaOptions);
    siswaSelect.addEventListener('change', refreshBarangOptions);
    filterSiswaOptions();
    const initialSiswa = siswaOptions.find(option => option.value === siswaSelect.value);
    if (initialSiswa) {
        selectSiswaOption(initialSiswa);
    }
    refreshBarangOptions();
</script>
@endsection
