<div class="form-grid">
    <div class="form-group">
        <label for="sekolah_id">Sekolah</label>
        <select name="sekolah_id" id="sekolah_id" required>
            <option value="">Pilih Sekolah</option>
            @foreach($sekolah as $item)
                <option value="{{ $item->id }}" {{ old('sekolah_id', $koperasi->sekolah_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nama_sekolah }}
                </option>
            @endforeach
        </select>
        @error('sekolah_id')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="kode_barang">Kode Barang</label>
        <input type="text" name="kode_barang" id="kode_barang" value="{{ old('kode_barang', $koperasi->kode_barang ?? '') }}" placeholder="Contoh: BUK-001">
        @error('kode_barang')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group full">
        <label for="nama_barang">Nama Barang</label>
        <input type="text" name="nama_barang" id="nama_barang" value="{{ old('nama_barang', $koperasi->nama_barang ?? '') }}" required placeholder="Contoh: Buku Cetak Tema 1">
        @error('nama_barang')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="kategori">Kategori</label>
        <select name="kategori" id="kategori" required>
            <option value="">Pilih Kategori</option>
            @foreach($kategoriList as $key => $label)
                <option value="{{ $key }}" {{ old('kategori', $koperasi->kategori ?? '') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('kategori')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="satuan">Satuan</label>
        <input type="text" name="satuan" id="satuan" value="{{ old('satuan', $koperasi->satuan ?? 'pcs') }}" required placeholder="pcs / set / buku">
        @error('satuan')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="jenis_transaksi">Jenis Transaksi</label>
        <select name="jenis_transaksi" id="jenis_transaksi" required>
            <option value="jual" {{ old('jenis_transaksi', $koperasi->jenis_transaksi ?? 'jual') == 'jual' ? 'selected' : '' }}>Dijual</option>
            <option value="sewa" {{ old('jenis_transaksi', $koperasi->jenis_transaksi ?? 'jual') == 'sewa' ? 'selected' : '' }}>Disewakan</option>
        </select>
        @error('jenis_transaksi')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="harga_beli">Harga Beli</label>
        <input type="number" name="harga_beli" id="harga_beli" min="0" step="100" value="{{ old('harga_beli', $koperasi->harga_beli ?? 0) }}">
        @error('harga_beli')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="harga_jual">Harga Jual</label>
        <input type="number" name="harga_jual" id="harga_jual" min="0" step="100" value="{{ old('harga_jual', $koperasi->harga_jual ?? 0) }}" required>
        @error('harga_jual')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="harga_sewa_siswa">Tarif Sewa Siswa PIIS</label>
        <input type="number" name="harga_sewa_siswa" id="harga_sewa_siswa" min="0" step="100" value="{{ old('harga_sewa_siswa', $koperasi->harga_sewa_siswa ?? '') }}" placeholder="Kosongkan untuk barang jual">
        @error('harga_sewa_siswa')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="harga_sewa_umum">Tarif Sewa Non-PIIS</label>
        <input type="number" name="harga_sewa_umum" id="harga_sewa_umum" min="0" step="100" value="{{ old('harga_sewa_umum', $koperasi->harga_sewa_umum ?? '') }}" placeholder="Kosongkan untuk barang jual">
        @error('harga_sewa_umum')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="harga_penggantian">Biaya Kehilangan</label>
        <input type="number" name="harga_penggantian" id="harga_penggantian" min="0" step="100" value="{{ old('harga_penggantian', $koperasi->harga_penggantian ?? '') }}" placeholder="Jika tercantum">
        @error('harga_penggantian')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="stok">Stok</label>
        <input type="number" name="stok" id="stok" min="0" value="{{ old('stok', $koperasi->stok ?? 0) }}" required>
        @error('stok')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="stok_minimum">Stok Minimum</label>
        <input type="number" name="stok_minimum" id="stok_minimum" min="0" value="{{ old('stok_minimum', $koperasi->stok_minimum ?? 5) }}" required>
        @error('stok_minimum')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="aktif" {{ old('status', $koperasi->status ?? 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="nonaktif" {{ old('status', $koperasi->status ?? 'aktif') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        @error('status')<small>{{ $message }}</small>@enderror
    </div>

    <div class="form-group full">
        <label for="deskripsi">Deskripsi</label>
        <textarea name="deskripsi" id="deskripsi" rows="4" placeholder="Catatan barang, ukuran, kelas, atau keterangan lain">{{ old('deskripsi', $koperasi->deskripsi ?? '') }}</textarea>
        @error('deskripsi')<small>{{ $message }}</small>@enderror
    </div>
</div>
