<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    protected $table = 'sekolah';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nama_sekolah',
        'kode_sekolah',
        'alamat',
        'kontak',
        'telepon',
        'email',
        'jenjang',
        'durasi_pendidikan',
    ];

    /**
     * Relasi ke tabel siswa
     * Satu sekolah bisa punya banyak siswa
     */
    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_sekolah'); // pastikan foreign key ini benar
    }

    /**
     * Relasi ke tabel kelas
     * Satu sekolah bisa punya banyak kelas
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'sekolah_id');
    }
    // ✅ Method untuk generate nomor kwitansi berikutnya
    public function getNextNomorKwitansi()
    {
        return app(\App\Services\ReceiptNumberGenerator::class)->school($this);
    }
}