<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPembayaran extends Model
{
    use HasFactory;

    protected $table = 'jenis_pembayaran';

    protected $fillable = [
        'sekolah_id',
        'tahun_ajaran_id',
        'nama_pembayaran',
        'tipe',
        'nominal',
        'jatuh_tempo',
        'target_type',
        'submission_token',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    /**
     * Nama yang dipakai pada daftar dan dokumen tagihan.
     */
    public function getNamaDenganTahunAttribute(): string
    {
        $tahunAjaran = trim((string) ($this->tahunAjaran?->label ?? ''));

        return $tahunAjaran !== ''
            ? $this->nama_pembayaran . ' - Tahun Ajaran ' . $tahunAjaran
            : $this->nama_pembayaran;
    }

    // Relasi many-to-many dengan siswa
    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'jenis_pembayaran_siswa')
                    ->withTimestamps();
    }

    // Relasi many-to-many dengan kelas
    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'jenis_pembayaran_kelas')
                    ->withTimestamps();
    }

    // ✅ PERBAIKAN: Helper method untuk mendapatkan siswa yang eligible
    public function getEligibleSiswa()
    {
        switch ($this->target_type) {
            case 'all':
                // ✅ PERBAIKAN: Gunakan id_sekolah bukan sekolah_id
                return Siswa::where('id_sekolah', $this->sekolah_id)->get();

            case 'specific_students':
                return $this->siswa;

            case 'specific_classes':
                // ✅ PERBAIKAN: Gunakan id_sekolah bukan sekolah_id
                return Siswa::whereIn('kelas_id', $this->kelas->pluck('id'))
                           ->where('id_sekolah', $this->sekolah_id)
                           ->get();

            default:
                return collect();
        }
    }

    // ✅ PERBAIKAN: Check if a student is eligible for this payment type
    public function isStudentEligible($siswaId)
    {
        $siswa = Siswa::find($siswaId);
        // ✅ PERBAIKAN: Gunakan id_sekolah bukan sekolah_id
        if (!$siswa || $siswa->id_sekolah != $this->sekolah_id) {
            return false;
        }

        switch ($this->target_type) {
            case 'all':
                return true;

            case 'specific_students':
                return $this->siswa()->where('siswa_id', $siswaId)->exists();

            case 'specific_classes':
                return $this->kelas()->where('kelas_id', $siswa->kelas_id)->exists();

            default:
                return false;
        }
    }
}
