<?php

namespace App\Support;

class AdminPermission
{
    public static function groups(): array
    {
        return [
            'Master Data Sekolah' => [
                'sekolah.manage' => 'Kelola Sekolah',
                'tahun_ajaran.manage' => 'Kelola Tahun Ajaran',
                'kelas.manage' => 'Kelola Kelas',
                'siswa.manage' => 'Kelola Data Siswa',
            ],
            'Pembayaran SPP & Tagihan' => [
                'jenis_pembayaran.manage' => 'Kelola Jenis Pembayaran',
                'tagihan.manage' => 'Kelola Tagihan Siswa',
                'pembayaran.process' => 'Proses Pembayaran & Kwitansi',
                'riwayat.view' => 'Lihat Riwayat Pembayaran',
            ],
            'Koperasi' => [
                'koperasi.barang.manage' => 'Kelola Barang Koperasi',
                'koperasi.stok.manage' => 'Kelola Stok Barang',
                'koperasi.penjualan.manage' => 'Penjualan Koperasi',
            ],
            'Keuangan Kas' => [
                'pemasukan.manage' => 'Kelola Pemasukan',
                'pengeluaran.manage' => 'Kelola Pengeluaran',
                'keuangan_kas.view' => 'Lihat Keuangan Kas',
            ],
            'Dompet Digital' => [
                'wallet.view' => 'Lihat dan pantau dompet siswa',
                'wallet.refund' => 'Refund transaksi dompet',
            ],
            'Kenaikan & Kelulusan' => [
                'kenaikan.manage' => 'Proses Kenaikan Kelas',
                'kelulusan.manage' => 'Proses Kelulusan Siswa',
            ],
            'Import & Export Data' => [
                'import_excel.manage' => 'Import Data Excel',
                'export_excel.manage' => 'Export Data Excel',
            ],
            'Laporan' => [
                'laporan.view' => 'Lihat Laporan',
                'laporan.export' => 'Export Laporan Excel',
            ],
            'Admin & Sistem' => [
                'admin.manage' => 'Kelola Admin',
                'admin.permissions.manage' => 'Atur Hak Akses Admin',
                'log.view' => 'Lihat Log Aktivitas',
                'log.delete' => 'Hapus Log Aktivitas',
                'backup.manage' => 'Backup Database',
            ],
        ];
    }

    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $permissions) => $permissions)
            ->all();
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function label(string $permission): string
    {
        return self::all()[$permission] ?? $permission;
    }
}
