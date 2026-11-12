<form class="billing-scope-form pi-compact-filter-form" id="billingScopeForm" method="GET" action="{{ route('tagihan.index.grouped') }}" data-native-compact data-filter-groups="off">
    <div class="pi-compact-toolbar">
            <label class="billing-search pi-compact-search pi-has-search-submit" for="billingStudentSearch">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input id="billingStudentSearch" name="table_search" type="search" value="{{ $billingTableSearch }}" placeholder="Cari nama, NIS, kelas…" aria-label="Cari siswa" title="Cari nama, NIS, sekolah, atau kelas" autocomplete="off">
                <button class="billing-search-clear {{ $billingTableSearch !== '' ? 'is-visible' : '' }}" id="billingSearchClear" type="button" aria-label="Hapus pencarian"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                <button class="pi-compact-search-submit" type="submit" aria-label="Cari siswa" title="Cari siswa"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/><path d="m13 13 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
            </label>
        <button type="button" class="pi-compact-filter-toggle" aria-controls="billing-filter-panel" aria-expanded="false" aria-label="Filter, {{ $billingActiveFilters }} aktif" popovertarget="billing-filter-panel">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5h14M5.5 10h9M8 15h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <span>Filter</span><span class="pi-compact-filter-count">{{ $billingActiveFilters ?: '' }}</span>
        </button>
            <div class="pi-table-sortbar pi-unified-table-tools" id="billing-table-toolbar" role="group" aria-label="Kontrol tabel">
                <span class="pi-table-meta">{{ number_format($studentPaginator->total(), 0, ',', '.') }} data</span>
                @if($studentRows->isNotEmpty())
                <button type="button" class="pi-table-options-button" aria-label="Atur tampilan tabel" title="Atur urutan dan tampilan tabel" aria-expanded="false">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14M7 3v4M13 8v4M8 13v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </button>
                @endif
            </div>
    </div>
    <section class="pi-compact-filter-panel" id="billing-filter-panel" role="region" aria-label="Pilihan filter" popover="auto" style="--pi-filter-width:520px;--pi-filter-columns:2">
        <div class="pi-compact-filter-heading">Filter data</div>
        <div class="pi-compact-filter-grid">
            <div class="billing-field pi-compact-field">
                <label for="billingSchool">Sekolah</label>
                <select id="billingSchool" name="sekolah">
                    <option value="" @selected(!$selectedSekolah)>Semua sekolah</option>
                    @foreach($sekolahData as $sekolah)
                        <option value="{{ $sekolah->id }}" @selected($selectedSekolah?->id === $sekolah->id)>{{ $sekolah->nama_sekolah }}</option>
                    @endforeach
                </select>
            </div>
            <div class="billing-field pi-compact-field">
                <label for="billingClass">Kelas</label>
                <select id="billingClass" name="kelas" data-class-filter-for="billingSchool" data-all-label="Semua kelas">
                    <option value="" @selected(!$selectedKelas)>Semua kelas</option>
                    @foreach($availableClasses as $kelas)
                        <option value="{{ $kelas->id }}" data-school-id="{{ $kelas->sekolah_id }}" data-school-name="{{ $kelas->sekolah?->nama_sekolah }}" data-class-label="{{ $classLabel($kelas) }} · {{ $kelas->siswa_count }} siswa" @selected($selectedKelas?->id === $kelas->id)>
                            @if(!$selectedSekolah){{ $kelas->sekolah?->nama_sekolah ?? 'Sekolah belum diatur' }} · @endif
                            {{ $classLabel($kelas) }} · {{ $kelas->siswa_count }} siswa
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="billing-field pi-compact-field">
                <label for="billingAcademicYear">Tahun ajaran</label>
                <select id="billingAcademicYear" name="tahun_ajaran">
                    @foreach($academicYearData as $tahun)
                        <option value="{{ $tahun->id }}" @selected($selectedTahunAjaran?->id === $tahun->id)>{{ $tahun->label }}{{ $tahun->aktif ? ' · Aktif' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="billing-field pi-compact-field">
                <label for="billingStatusFilter">Status tagihan</label>
                <select id="billingStatusFilter" class="pi-compact-status-select" name="table_status">
                    @foreach(['all' => 'Semua', 'open' => 'Belum lunas', 'belum_jatuh_tempo' => 'Belum jatuh tempo', 'sebagian' => 'Sebagian', 'lunas' => 'Lunas'] as $value => $label)
                        <option value="{{ $value }}" @selected($billingTableStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="pi-compact-panel-actions">
            <button type="button" class="pi-compact-close" popovertarget="billing-filter-panel" popovertargetaction="hide">Tutup</button>
            <button type="submit" class="pi-compact-apply pi-tone-primary">Terapkan filter</button>
        </div>
    </section>
</form>
