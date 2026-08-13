<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\JenisPembayaranController;
use App\Http\Controllers\KoperasiController;
use App\Http\Controllers\KoperasiPenjualanController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Siswa\AuthController as SiswaAuthController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\Auth\LoginController as SiswaLoginController;
use App\Http\Controllers\Siswa\TagihanSiswaController;
use App\Http\Controllers\Siswa\RiwayatPembayaranController;
use App\Http\Controllers\Siswa\ProfileSiswaController;
use App\Http\Controllers\BackupDatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\SiswaImportController;
use App\Http\Controllers\Admin\SiswaExportController;
use App\Http\Controllers\Laporan\LaporanAdminController;
use App\Http\Controllers\Laporan\LaporanJenisPembayaranController;
use App\Http\Controllers\Laporan\LaporanPembayaranController;
use App\Http\Controllers\Laporan\LaporanPengeluaranController;
use App\Http\Controllers\Laporan\LaporanPemasukanController;
use App\Http\Controllers\Laporan\LaporanKoperasiController;
use App\Http\Controllers\Laporan\LaporanSekolahController;
use App\Http\Controllers\Laporan\LaporanSiswaController;
use App\Http\Controllers\Laporan\LaporanTahunAjaranController;
use App\Http\Controllers\Laporan\LaporanKenaikanController;
use App\Http\Controllers\KelulusanKelasController;
use App\Http\Controllers\Laporan\LaporanKelulusanController;



/*
|--------------------------------------------------------------------------
| ROUTE LOGIN/LOGOUT
|--------------------------------------------------------------------------
*/

// ✅ LOGIN & LOGOUT ADMIN
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::prefix('siswa')->group(function () {
    // ✅ Form login siswa
    Route::get('/login', [SiswaAuthController::class, 'showLoginForm'])->name('siswa.login');
    // ✅ Proses login siswa
    Route::post('/login', [SiswaAuthController::class, 'login'])->name('siswa.login.store');
    // ✅ Logout siswa
    Route::post('/logout', [SiswaAuthController::class, 'logout'])->name('siswa.logout');

// ✅ Dashboard siswa (butuh middleware guard siswa)
Route::get('/dashboard', [SiswaDashboardController::class, 'index'])
        ->middleware('auth:siswa')
        ->name('siswa.dashboard');
});
Route::prefix('siswa')->middleware('auth:siswa')->group(function () {
    Route::get('/tagihan', [TagihanSiswaController::class, 'index'])->name('siswa.tagihan.index');
    Route::get('/riwayat-pembayaran', [RiwayatPembayaranController::class, 'index'])->name('siswa.riwayat.index');
    Route::get('/profil', [ProfileSiswaController::class, 'index'])->name('siswa.profil.index');
    Route::get('/profil/edit-password', [ProfileSiswaController::class, 'editPassword'])->name('siswa.profil.editPassword');
    Route::put('/profil/update-password', [ProfileSiswaController::class, 'updatePassword'])->name('siswa.profil.updatePassword');
});


/*
|--------------------------------------------------------------------------
| HOMEPAGE
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD ADMIN
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:web', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:web')->group(function () {
    // Profil Admin
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    

    // Resource routes
    Route::resource('admin', AdminController::class)->except(['show'])->middleware('permission:admin.manage');
    Route::resource('sekolah', SekolahController::class)->middleware('permission:sekolah.manage,kelas.manage');
    Route::resource('tahun_ajaran', TahunAjaranController::class)->except(['show'])->middleware('permission:tahun_ajaran.manage');
    Route::resource('kelas', KelasController::class)->except(['show'])->middleware('permission:kelas.manage');
    Route::resource('siswa', SiswaController::class)->middleware('permission:siswa.manage');
    Route::get('/get-kelas-by-sekolah/{sekolah_id}', [SiswaController::class, 'getBySekolah'])
        ->middleware('permission:siswa.manage,tagihan.manage')
        ->name('siswa.kelas_by_sekolah');
    Route::resource('jenis_pembayaran', JenisPembayaranController::class)->except(['show'])->middleware('permission:jenis_pembayaran.manage');
    Route::get('/jenis-pembayaran/get-data-by-sekolah/{sekolahId}', [JenisPembayaranController::class, 'getDataBySekolah'])
        ->middleware('permission:jenis_pembayaran.manage,tagihan.manage');
    Route::get('/koperasi/penjualan', [KoperasiPenjualanController::class, 'index'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.index');
    Route::get('/koperasi/penjualan/create', [KoperasiPenjualanController::class, 'create'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.create');
    Route::post('/koperasi/penjualan', [KoperasiPenjualanController::class, 'store'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.store');
    Route::get('/koperasi/penjualan/{id}', [KoperasiPenjualanController::class, 'show'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.show');
    Route::get('/koperasi/penjualan/{id}/kwitansi', [KoperasiPenjualanController::class, 'cetakKwitansi'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.kwitansi');
    Route::delete('/koperasi/penjualan/{id}', [KoperasiPenjualanController::class, 'destroy'])->middleware('permission:koperasi.penjualan.manage')->name('koperasi.penjualan.destroy');
    Route::get('/koperasi/{id}/stok', [KoperasiController::class, 'editStok'])->middleware('permission:koperasi.stok.manage')->name('koperasi.stok.edit');
    Route::put('/koperasi/{id}/stok', [KoperasiController::class, 'updateStok'])->middleware('permission:koperasi.stok.manage')->name('koperasi.stok.update');
    Route::resource('koperasi', KoperasiController::class)->except(['show'])->middleware('permission:koperasi.barang.manage');

    // Tagihan
    Route::get('/tagihan', [TagihanController::class, 'index'])->middleware('permission:tagihan.manage,pembayaran.process')->name('tagihan.index');
    Route::get('/tagihan/original', [TagihanController::class, 'indexOriginal'])->middleware('permission:tagihan.manage,pembayaran.process')->name('tagihan.index.original');
    Route::get('/tagihan/grouped', [TagihanController::class, 'indexGrouped'])->middleware('permission:tagihan.manage,pembayaran.process')->name('tagihan.index.grouped');
    Route::get('/tagihan/proses-siswa/{siswaId}', [TagihanController::class, 'prosesSiswa'])->middleware('permission:tagihan.manage,pembayaran.process')->name('tagihan.proses.siswa');
    Route::post('/tagihan/generate-manual', [TagihanController::class, 'generateTagihanManual'])->middleware('permission:tagihan.manage')->name('tagihan.generate.manual');
    Route::post('/tagihan/generate-manual-siswa/{siswaId}', [TagihanController::class, 'generateTagihanManualSiswa'])->middleware('permission:tagihan.manage')->name('tagihan.generate.manual.siswa');
    Route::post('/tagihan/proses-multi', [TagihanController::class, 'prosesMultiPembayaran'])->middleware('permission:pembayaran.process')->name('tagihan.proses.multi');
    Route::get('/tagihan/sinkronisasi-nama', [TagihanController::class, 'sinkronisasiNama'])->middleware('permission:tagihan.manage')->name('tagihan.sinkronisasi.nama');
    Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'destroy'])->middleware('permission:tagihan.manage')->name('tagihan.destroy');
    Route::get('/tagihan/get-students-summary/{sekolahId}/{kelasId}', [TagihanController::class, 'getStudentsSummary'])->middleware('permission:tagihan.manage,pembayaran.process')->name('tagihan.students.summary');
    
    // Riwayat & Kenaikan Kelas
    Route::get('/riwayat', [PembayaranController::class, 'index'])->middleware('permission:riwayat.view')->name('riwayat.index');
    Route::post('/pembayaran/store', [\App\Http\Controllers\PembayaranController::class, 'store'])->middleware('permission:pembayaran.process')->name('pembayaran.store');
    Route::get('/pembayaran/koreksi/{id}', [PembayaranController::class, 'editCorrection'])->middleware('permission:pembayaran.process')->name('pembayaran.koreksi.edit');
    Route::put('/pembayaran/koreksi/{id}', [PembayaranController::class, 'updateCorrection'])->middleware('permission:pembayaran.process')->name('pembayaran.koreksi.update');
    Route::get('/pembayaran/kwitansi/{id}', [PembayaranController::class, 'cetakKwitansi'])->middleware('permission:pembayaran.process,riwayat.view')->name('pembayaran.kwitansi');
    Route::get('/pembayaran/kwitansi/{id}/pdf', [PembayaranController::class, 'unduhKwitansiPdf'])->middleware('permission:pembayaran.process,riwayat.view')->name('pembayaran.kwitansi.pdf');
    Route::get('/pembayaran/kwitansi-grup', [PembayaranController::class, 'cetakKwitansiGrup'])->middleware('permission:pembayaran.process,riwayat.view')->name('pembayaran.kwitansi.grup');
    Route::get('/pembayaran/kwitansi-grup/pdf', [PembayaranController::class, 'unduhKwitansiGrupPdf'])->middleware('permission:pembayaran.process,riwayat.view')->name('pembayaran.kwitansi.grup.pdf');
    Route::get('/kenaikan-kelas', [KenaikanKelasController::class, 'index'])->middleware('permission:kenaikan.manage')->name('kenaikan.index');
    Route::post('/kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->middleware('permission:kenaikan.manage')->name('kenaikan.proses');
    Route::post('/kenaikan/cancel/{siswa_id}', [KenaikanKelasController::class, 'cancelPromotion'])->middleware('permission:kenaikan.manage')->name('kenaikan.cancel');
    
    Route::resource('pemasukan', \App\Http\Controllers\PemasukanController::class)->middleware('permission:pemasukan.manage');
    Route::resource('pengeluaran', \App\Http\Controllers\PengeluaranController::class)->except(['show'])->middleware('permission:pengeluaran.manage');
    Route::get('/keuangan-kas', [\App\Http\Controllers\KeuanganKasController::class, 'index'])
         ->middleware('permission:keuangan_kas.view')
         ->name('keuangan.kas.index');

    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->middleware('permission:log.view')->name('log_aktivitas.index');
    Route::get('/log-aktivitas/{id}', [LogAktivitasController::class, 'show'])->middleware('permission:log.view')->name('log_aktivitas.show');
    Route::delete('/log-aktivitas/{id}', [LogAktivitasController::class, 'destroy'])->middleware('permission:log.delete')->name('log_aktivitas.destroy');
    Route::delete('/log-aktivitas', [LogAktivitasController::class, 'destroyAll'])->middleware('permission:log.delete')->name('log_aktivitas.destroyAll');

    Route::get('/import_excel', [SiswaImportController::class, 'showImportForm'])->middleware('permission:import_excel.manage')->name('import.form');
    Route::get('import_excel/template', [SiswaImportController::class, 'downloadTemplate'])->middleware('permission:import_excel.manage')->name('import.template');
    Route::post('import_excel', [SiswaImportController::class, 'import'])->middleware('permission:import_excel.manage')->name('import');

        // Halaman export
    Route::get('export-excel', [\App\Http\Controllers\SiswaExportController::class, 'index'])
        ->middleware('permission:export_excel.manage')
        ->name('export_excel.index');

    // Tombol export (download)
    Route::get('export-excel/download', [\App\Http\Controllers\SiswaExportController::class, 'export'])
        ->middleware('permission:export_excel.manage')
        ->name('export_excel.download');

    
    Route::get('/laporan/admin', [LaporanAdminController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.admin');
    Route::get('/laporan/admin/excel', [LaporanAdminController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.admin.excel');
    Route::get('/laporan/jenis-pembayaran', [LaporanJenisPembayaranController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.jenis_pembayaran');
    Route::get('/laporan/jenis-pembayaran/excel', [LaporanJenisPembayaranController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.jenis_pembayaran.excel');
    Route::get('/laporan/kelas', function () {
        return redirect()->route('laporan.sekolah', request()->query());
    })->middleware('permission:laporan.view')->name('laporan.kelas');
    Route::get('/laporan/kelas/excel', function () {
        return redirect()->route('laporan.sekolah.excel', request()->query());
    })->middleware('permission:laporan.export')->name('laporan.kelas.excel');
    
Route::get('/laporan/pembayaran', [LaporanPembayaranController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.pembayaran');
Route::get('/laporan/pembayaran/excel', [LaporanPembayaranController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.pembayaran.excel');
Route::get('/laporan/pengeluaran', [LaporanPengeluaranController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.pengeluaran');
Route::get('/laporan/pengeluaran/excel', [LaporanPengeluaranController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.pengeluaran.excel');


Route::get('/laporan/pemasukan', [LaporanPemasukanController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.pemasukan');
Route::get('/laporan/pemasukan/excel', [LaporanPemasukanController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.pemasukan.excel');

Route::get('/laporan/koperasi', [LaporanKoperasiController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.koperasi');
Route::get('/laporan/koperasi/excel', [LaporanKoperasiController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.koperasi.excel');


Route::get('/laporan/sekolah', [LaporanSekolahController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.sekolah');
Route::get('/laporan/sekolah/excel', [LaporanSekolahController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.sekolah.excel');

Route::get('/laporan/siswa', [LaporanSiswaController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.siswa');
Route::get('/laporan/siswa/excel', [LaporanSiswaController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.siswa.excel');

Route::get('/laporan/tahun-ajaran', [LaporanTahunAjaranController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.tahun_ajaran');
Route::get('/laporan/tahun-ajaran/excel', [LaporanTahunAjaranController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.tahun_ajaran.excel');

// Halaman daftar siswa kelas tingkat 6
Route::get('/kelulusan-kelas', [KelulusanKelasController::class, 'index'])->middleware('permission:kelulusan.manage')->name('kelulusan.index');

// Aksi untuk ubah status jadi lulus
Route::post('/kelulusan-kelas/{id}/update-status', [KelulusanKelasController::class, 'updateStatus'])->middleware('permission:kelulusan.manage')->name('kelulusan.updateStatus');

Route::get('/laporan/kenaikan', [LaporanKenaikanController::class, 'index'])->middleware('permission:laporan.view')->name('laporan.kenaikan');
Route::get('/laporan/kenaikan/excel', [LaporanKenaikanController::class, 'exportExcel'])->middleware('permission:laporan.export')->name('laporan.kenaikan.excel');

Route::get('/laporan/kelulusan', [LaporanKelulusanController::class, 'index'])
    ->middleware('permission:laporan.view')
    ->name('laporan.kelulusan');

Route::get('/laporan/kelulusan/excel', [LaporanKelulusanController::class, 'exportExcel'])
    ->middleware('permission:laporan.export')
    ->name('laporan.kelulusan.excel');

});

Route::middleware('auth:web')->group(function () {
    Route::get('/backup', [\App\Http\Controllers\BackupDatabaseController::class, 'index'])
         ->middleware('permission:backup.manage')
         ->name('backup.index');

    Route::post('/backup/create', [\App\Http\Controllers\BackupDatabaseController::class, 'create'])
         ->middleware('permission:backup.manage')
         ->name('backup.create');

    Route::get('/backup/download/{file}', [BackupDatabaseController::class, 'download'])
    ->middleware('permission:backup.manage')
    ->where('file', '.*') // 🔥 menangkap path termasuk tanda '/'
    ->name('backup.download');
});
