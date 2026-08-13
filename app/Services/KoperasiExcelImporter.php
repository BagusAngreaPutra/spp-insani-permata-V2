<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class KoperasiExcelImporter
{
    private const SOURCE_DATE = '30 Juli 2026';

    public function parse(string $path, int $primarySchoolId, int $tkSchoolId): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("File Excel tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);

        try {
            $uniformSheet = $this->sheet($workbook, 'Jual beli seragam');
            $bookSheet = $this->sheet($workbook, 'jual beli buku');
            $rentalSheet = $this->sheet($workbook, 'Sewa');

            $items = array_merge(
                $this->parseUniforms($uniformSheet, $primarySchoolId, $tkSchoolId),
                $this->parseBooks($bookSheet, $primarySchoolId, $tkSchoolId),
                $this->parseRentals($rentalSheet, $primarySchoolId)
            );

            $codes = array_column($items, 'kode_barang');

            if (count($codes) !== count(array_unique($codes))) {
                throw new RuntimeException('Kode barang hasil impor tidak unik. Impor dibatalkan.');
            }

            return $items;
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function parseUniforms(Worksheet $sheet, int $primarySchoolId, int $tkSchoolId): array
    {
        $items = [];
        $sizes = [
            ['stock' => 'C', 'price' => 'D', 'label' => 'S'],
            ['stock' => 'E', 'price' => 'F', 'label' => 'M'],
            ['stock' => 'G', 'price' => 'H', 'label' => 'L'],
            ['stock' => 'I', 'price' => 'J', 'label' => 'XL'],
            ['stock' => 'K', 'price' => 'L', 'label' => 'XXL'],
            ['stock' => 'M', 'price' => 'N', 'label' => 'Jumbo'],
        ];

        $gender = 'Putri';
        for ($row = 5; $row <= 15; $row++) {
            $sourceName = $this->text($sheet, "B{$row}");
            if ($sourceName === null) {
                continue;
            }

            [$sourceName, $gender] = $this->stripGenderPrefix($sourceName, $gender);
            $product = $this->cleanKnownTypos($sourceName);
            $this->appendSizedItems(
                $items,
                $sheet,
                $row,
                $sizes,
                "Seragam TK {$gender} - {$product}",
                'seragam',
                $tkSchoolId,
                "uniform|tk|{$row}",
                'Jual beli seragam'
            );
        }

        for ($row = 17; $row <= 28; $row++) {
            $product = $this->text($sheet, "B{$row}");
            if ($product === null) {
                continue;
            }

            $this->appendSizedItems(
                $items,
                $sheet,
                $row,
                $sizes,
                'Seragam SD Putri - '.$this->cleanKnownTypos($product),
                'seragam',
                $primarySchoolId,
                "uniform|sd|putri|{$row}",
                'Jual beli seragam'
            );
        }

        for ($row = 30; $row <= 37; $row++) {
            $product = $this->text($sheet, "B{$row}");
            if ($product === null) {
                continue;
            }

            $this->appendSizedItems(
                $items,
                $sheet,
                $row,
                $sizes,
                'Seragam SD Putra - '.$this->cleanKnownTypos($product),
                'seragam',
                $primarySchoolId,
                "uniform|sd|putra|{$row}",
                'Jual beli seragam'
            );
        }

        $this->appendSizedItems(
            $items,
            $sheet,
            39,
            $sizes,
            'Seragam SD Putra/Putri - Baju & Celana Olahraga',
            'seragam',
            $primarySchoolId,
            'uniform|sd|putra-putri|39-40',
            'Jual beli seragam',
            'Stok dan harga pada Excel digabung untuk baris baju dan celana olahraga.'
        );

        $this->appendSizedItems(
            $items,
            $sheet,
            41,
            $sizes,
            'Seragam SD Putri - Rok Olahraga',
            'seragam',
            $primarySchoolId,
            'uniform|sd|putri|41',
            'Jual beli seragam',
            'Harga belum tercantum pada Excel.'
        );

        $this->appendSizedItems(
            $items,
            $sheet,
            42,
            $sizes,
            'Seragam SD Putri - Jilbab Olahraga',
            'seragam',
            $primarySchoolId,
            'uniform|sd|putri|42',
            'Jual beli seragam'
        );

        $peciSizes = [
            ['stock' => 'C', 'price' => 'D', 'label' => '2'],
            ['stock' => 'E', 'price' => 'F', 'label' => '3'],
            ['stock' => 'G', 'price' => 'H', 'label' => '4'],
            ['stock' => 'I', 'price' => 'J', 'label' => '5'],
            ['stock' => 'K', 'price' => 'L', 'label' => '6'],
            ['stock' => 'M', 'price' => 'N', 'label' => '7'],
        ];

        foreach ([44, 45] as $row) {
            $product = $this->text($sheet, "B{$row}");
            if ($product === null) {
                continue;
            }

            $this->appendSizedItems(
                $items,
                $sheet,
                $row,
                $peciSizes,
                $this->cleanKnownTypos($product),
                'atribut',
                $primarySchoolId,
                "attribute|peci|{$row}",
                'Jual beli seragam'
            );
        }

        for ($row = 47; $row <= 54; $row++) {
            $product = $this->text($sheet, "B{$row}");
            $stock = $this->integer($sheet, "C{$row}");
            $price = $this->money($sheet, "D{$row}");

            if ($product === null || ($stock === null && $price === null)) {
                continue;
            }

            $items[] = $this->item(
                "attribute|{$row}",
                $primarySchoolId,
                $this->cleanKnownTypos($product),
                'atribut',
                'jual',
                'pcs',
                $price ?? 0,
                $stock ?? 0,
                $price !== null,
                'Jual beli seragam',
                $row,
                $price === null ? 'Harga belum tercantum pada Excel.' : null
            );
        }

        return $items;
    }

    private function parseBooks(Worksheet $sheet, int $primarySchoolId, int $tkSchoolId): array
    {
        $items = [];

        for ($row = 4; $row <= 32; $row++) {
            $sourceName = $this->text($sheet, "B{$row}");
            $price = $this->money($sheet, "C{$row}");

            if ($sourceName === null || $price === null) {
                continue;
            }

            $name = $this->cleanKnownTypos($sourceName);
            $schoolId = preg_match('/\bTK\b/i', $name) ? $tkSchoolId : $primarySchoolId;

            $items[] = $this->item(
                "book|{$row}",
                $schoolId,
                $name,
                'buku',
                'jual',
                'buku',
                $price,
                0,
                true,
                'jual beli buku',
                $row,
                'Jumlah stok tidak tercantum pada Excel; stok awal disimpan 0.'
            );
        }

        return $items;
    }

    private function parseRentals(Worksheet $sheet, int $primarySchoolId): array
    {
        $items = [];
        $rows = [6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

        foreach ($rows as $row) {
            $sourceName = $this->text($sheet, "B{$row}");
            $stock = $this->integer($sheet, "C{$row}");

            if ($sourceName === null || $stock === null) {
                continue;
            }

            $sharedGraduationRate = $row >= 10 && $row <= 13;
            $rateRow = $sharedGraduationRate ? 10 : $row;
            $studentRate = $this->money($sheet, "D{$rateRow}");
            $publicRate = $this->money($sheet, "E{$rateRow}");
            $replacementPrice = $this->money($sheet, "F{$rateRow}");
            $name = $this->cleanKnownTypos($sourceName);
            $status = ! str_contains(mb_strtolower($name), 'rusak')
                && ($studentRate !== null || $publicRate !== null);

            $notes = [];
            if ($sharedGraduationRate) {
                $notes[] = 'Tarif pada Excel digabung untuk perlengkapan wisuda (topi, baju, teratai, dan medali).';
            }
            if (! $status) {
                $notes[] = 'Barang dinonaktifkan karena tarif belum tercantum atau kondisinya ditulis rusak.';
            }

            $items[] = array_merge(
                $this->item(
                    "rental|{$row}",
                    $primarySchoolId,
                    $name,
                    $this->rentalCategory($name),
                    'sewa',
                    'pcs',
                    0,
                    $stock,
                    $status,
                    'Sewa',
                    $row,
                    implode(' ', $notes) ?: null
                ),
                [
                    'harga_sewa_siswa' => $studentRate,
                    'harga_sewa_umum' => $publicRate,
                    'harga_penggantian' => $replacementPrice,
                ]
            );
        }

        return $items;
    }

    private function appendSizedItems(
        array &$items,
        Worksheet $sheet,
        int $row,
        array $sizes,
        string $baseName,
        string $category,
        int $schoolId,
        string $sourceKey,
        string $sheetName,
        ?string $extraNote = null
    ): void {
        foreach ($sizes as $size) {
            $stock = $this->integer($sheet, $size['stock'].$row);
            $price = $this->money($sheet, $size['price'].$row);

            if ($stock === null && $price === null) {
                continue;
            }

            $notes = array_filter([
                $price === null ? 'Harga belum tercantum pada Excel.' : null,
                $extraNote,
            ]);

            $items[] = $this->item(
                $sourceKey.'|size|'.$size['label'],
                $schoolId,
                $baseName.' - Ukuran '.$size['label'],
                $category,
                'jual',
                'pcs',
                $price ?? 0,
                $stock ?? 0,
                $price !== null,
                $sheetName,
                $row,
                implode(' ', $notes) ?: null
            );
        }
    }

    private function item(
        string $sourceKey,
        int $schoolId,
        string $name,
        string $category,
        string $transactionType,
        string $unit,
        int $salePrice,
        int $stock,
        bool $active,
        string $sheet,
        int $row,
        ?string $note = null
    ): array {
        $description = "Sumber Excel: {$sheet}, baris {$row}. Data stok per ".self::SOURCE_DATE.'.';
        if ($note) {
            $description .= ' '.$note;
        }

        return [
            'sekolah_id' => $schoolId,
            'kode_barang' => 'KOP-260730-'.strtoupper(substr(hash('sha256', $sourceKey), 0, 12)),
            'nama_barang' => $this->cleanText($name),
            'kategori' => $category,
            'jenis_transaksi' => $transactionType,
            'satuan' => $unit,
            'harga_beli' => 0,
            'harga_jual' => $salePrice,
            'harga_sewa_siswa' => null,
            'harga_sewa_umum' => null,
            'harga_penggantian' => null,
            'stok' => $stock,
            'stok_minimum' => 5,
            'deskripsi' => $description,
            'status' => $active ? 'aktif' : 'nonaktif',
        ];
    }

    private function sheet(Spreadsheet $workbook, string $name): Worksheet
    {
        $sheet = $workbook->getSheetByName($name);

        if (! $sheet) {
            throw new RuntimeException("Sheet '{$name}' tidak ditemukan di file Excel.");
        }

        return $sheet;
    }

    private function text(Worksheet $sheet, string $coordinate): ?string
    {
        $value = $sheet->getCell($coordinate)->getValue();
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->cleanText((string) $value);
    }

    private function integer(Worksheet $sheet, string $coordinate): ?int
    {
        $value = $sheet->getCell($coordinate)->getValue();
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $normalized = preg_replace('/[^0-9-]/', '', (string) $value);

        return $normalized === '' ? null : (int) $normalized;
    }

    private function money(Worksheet $sheet, string $coordinate): ?int
    {
        $value = $sheet->getCell($coordinate)->getValue();
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $normalized = preg_replace('/[^0-9-]/', '', (string) $value);

        return $normalized === '' ? null : (int) $normalized;
    }

    private function stripGenderPrefix(string $name, string $currentGender): array
    {
        if (preg_match('/^PR\s*:\s*/i', $name)) {
            return [preg_replace('/^PR\s*:\s*/i', '', $name), 'Putri'];
        }

        if (preg_match('/^LK\s*:\s*/i', $name)) {
            return [preg_replace('/^LK\s*:\s*/i', '', $name), 'Putra'];
        }

        return [$name, $currentGender];
    }

    private function cleanKnownTypos(string $name): string
    {
        $name = preg_replace('/^Buju\b/i', 'Buku', $name);
        $name = preg_replace('/^Batuk kotak$/i', 'Baju kotak', $name);
        $name = preg_replace('/^Terartai\b/i', 'Teratai', $name);

        return $this->cleanText($name);
    }

    private function cleanText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function rentalCategory(string $name): string
    {
        $name = mb_strtolower($name);

        if (preg_match('/seragam|pakaian|baju/', $name)) {
            return 'seragam';
        }

        if (preg_match('/topi|teratai|medali|kantong|slempang|songket/', $name)) {
            return 'atribut';
        }

        return 'lainnya';
    }
}
