<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanSiswaController extends Controller
{
   public function index(Request $request)
    {
        // Untuk kop laporan
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        // Ambil data filter dari request
        $search = $request->input('search');
        $sekolahId = $request->input('sekolah_id');
        $kelasId = $request->input('kelas_id');

        if ($sekolahId && $kelasId
            && !\App\Models\Kelas::whereKey($kelasId)->where('sekolah_id', $sekolahId)->exists()) {
            $kelasId = null;
            $request->merge(['kelas_id' => null]);
        }

        // Query dengan filter dinamis dan relasi
        $siswas = Siswa::with(['kelas.sekolah'])
            ->select(
                'id',
                'nis',
                'nama',
                'jenis_kelamin',
                'kelas_id',
                'tanggal_lahir',
                'status',
                'nominal_spp',
                'agama',
                'tempat_tinggal',
                'moda_transportasi',
                'nama_ayah',
                'nik_ayah',
                'pekerjaan_ayah',
                'penghasilan_ayah',
                'nama_ibu',
                'nik_ibu',
                'pekerjaan_ibu',
                'penghasilan_ibu',
                'no_telp_rumah',
                'no_hp',
                'email',
                'alamat',
                'id_sekolah'
            )
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%$search%");
            })
            ->when($sekolahId, function ($query, $sekolahId) {
                $query->where('id_sekolah', $sekolahId);
            })
            ->when($kelasId, function ($query, $kelasId) {
                $query->where('kelas_id', $kelasId);
            })
            ->get();

        // Untuk dropdown filter
        $daftarSekolah = \App\Models\Sekolah::all();
        $daftarKelas = \App\Models\Kelas::with('sekolah')
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        // Tanggal laporan
        $tanggalLaporan = \Carbon\Carbon::now()->translatedFormat('d F Y');

        return view('laporan.siswa', compact(
            'sekolah',
            'siswas',
            'tanggalLaporan',
            'daftarSekolah',
            'daftarKelas',
            'search',
            'sekolahId',
            'kelasId'
        ));
    }


    public function exportExcel()
    {
        $siswas = Siswa::select(
            'nis',
            'nama',
            'jenis_kelamin',
            'kelas_id',
            'tanggal_lahir',
            'status',
            'nominal_spp',
            'agama',
            'tempat_tinggal',
            'moda_transportasi',
            'nama_ayah',
            'nik_ayah',
            'pekerjaan_ayah',
            'penghasilan_ayah',
            'nama_ibu',
            'nik_ibu',
            'pekerjaan_ibu',
            'penghasilan_ibu',
            'no_telp_rumah',
            'no_hp',
            'email',
            'alamat'
        )->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Laporan Data Siswa');
        $sheet->mergeCells('A1:Y1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $headers = [
            'No', 'NIS', 'Nama', 'Jenis Kelamin', 'Kelas ID', 'Tanggal Lahir', 'Status', 'Nominal SPP',
            'Agama', 'Tempat Tinggal', 'Moda Transportasi',
            'Nama Ayah', 'NIK Ayah', 'Pekerjaan Ayah', 'Penghasilan Ayah',
            'Nama Ibu', 'NIK Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu',
            'No Telp Rumah', 'No HP', 'Email', 'Alamat'
        ];

        $colIndex = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($colIndex.'3', $header);
            $colIndex++;
        }

        $sheet->getStyle('A3:' . chr(ord('A') + count($headers) - 1) . '3')->getFont()->setBold(true);

        // Data
        $row = 4;
        $no = 1;
        foreach ($siswas as $s) {
            $data = [
                $no++,
                $s->nis,
                $s->nama,
                $s->jenis_kelamin ?? '-',
                $s->kelas_id,
                $s->tanggal_lahir ?? '-',
                $s->status,
                $s->nominal_spp,
                $s->agama,
                $s->tempat_tinggal,
                $s->moda_transportasi,
                $s->nama_ayah,
                $s->nik_ayah,
                $s->pekerjaan_ayah,
                $s->penghasilan_ayah,
                $s->nama_ibu,
                $s->nik_ibu,
                $s->pekerjaan_ibu,
                $s->penghasilan_ibu,
                $s->no_telp_rumah,
                $s->no_hp,
                $s->email,
                $s->alamat
            ];

            $col = 'A';
            foreach ($data as $val) {
                $sheet->setCellValue($col.$row, $val);
                $col++;
            }
            $row++;
        }

        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Siswa_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
