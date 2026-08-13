<?php

namespace App\Console\Commands;

use App\Models\Koperasi;
use App\Models\Sekolah;
use App\Services\KoperasiExcelImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ImportKoperasiExcel extends Command
{
    protected $signature = 'koperasi:import-excel
        {file? : Lokasi file Excel stok koperasi}
        {--primary-school=SDIT : Kode sekolah untuk barang umum dan SD}
        {--tk-school=TKIT : Kode sekolah untuk barang TK}
        {--update-existing : Perbarui lagi barang dari impor yang pernah dijalankan}
        {--dry-run : Validasi dan tampilkan ringkasan tanpa menulis database}';

    protected $description = 'Mengimpor stok, harga jual, dan tarif sewa koperasi dari workbook resmi';

    public function handle(KoperasiExcelImporter $importer): int
    {
        $path = $this->resolvePath($this->argument('file'));

        if (! is_file($path)) {
            $this->error("File Excel tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        if (! Schema::hasColumns('koperasi', [
            'jenis_transaksi',
            'harga_sewa_siswa',
            'harga_sewa_umum',
            'harga_penggantian',
        ])) {
            $this->error('Struktur tabel koperasi belum terbaru. Jalankan php artisan migrate terlebih dahulu.');

            return self::FAILURE;
        }

        $primarySchool = Sekolah::where('kode_sekolah', $this->option('primary-school'))->first();
        $tkSchool = Sekolah::where('kode_sekolah', $this->option('tk-school'))->first();

        if (! $primarySchool || ! $tkSchool) {
            $this->error('Sekolah tujuan tidak ditemukan. Periksa opsi --primary-school dan --tk-school.');

            return self::FAILURE;
        }

        try {
            $items = $importer->parse($path, $primarySchool->id, $tkSchool->id);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Workbook valid: '.count($items).' baris barang siap diimpor.');
        $this->table(
            ['Kelompok', 'Jumlah', 'Total stok'],
            collect($items)
                ->groupBy(fn (array $item) => $item['jenis_transaksi'].' / '.$item['kategori'])
                ->map(fn ($rows, $group) => [$group, $rows->count(), $rows->sum('stok')])
                ->values()
                ->all()
        );

        if ($this->option('dry-run')) {
            $this->comment('Dry-run selesai; database tidak diubah.');

            return self::SUCCESS;
        }

        $importCodes = array_column($items, 'kode_barang');
        $existingImportCount = Koperasi::whereIn('kode_barang', $importCodes)->count();

        if (! $this->option('update-existing') && $existingImportCount === count($items)) {
            $this->comment('Seluruh data workbook ini sudah ada; tidak ada perubahan database.');

            return self::SUCCESS;
        }

        $this->createBackup();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($items, &$created, &$updated, &$skipped): void {
            foreach ($items as $item) {
                $existing = Koperasi::where('kode_barang', $item['kode_barang'])->first();

                if ($existing && ! $this->option('update-existing')) {
                    $skipped++;

                    continue;
                }

                if ($existing) {
                    $existing->update($item);
                    $updated++;

                    continue;
                }

                Koperasi::create($item);
                $created++;
            }
        });

        $this->newLine();
        $this->info("Impor selesai: {$created} ditambahkan, {$updated} diperbarui, {$skipped} dilewati.");

        return self::SUCCESS;
    }

    private function resolvePath(?string $argument): string
    {
        $path = $argument ?: 'barang2 dikoperasi. stok tgl 30.7.26.xlsx';

        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function createBackup(): void
    {
        $directory = storage_path('app/import-backups');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/koperasi-before-excel-import-'.now()->format('Ymd-His').'.json';

        File::put(
            $path,
            json_encode(
                Koperasi::query()->orderBy('id')->get()->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        $this->comment("Cadangan tabel koperasi dibuat: {$path}");
    }
}
