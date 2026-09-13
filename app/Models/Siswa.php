<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Siswa extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'siswa';

    protected $fillable = [
        'id_sekolah',
        'tahun_ajaran_id',
        'kelas_id',
        'nis',
        'nama',
        'username',
        'password',
        'password_raw',   // ✅ Tambahkan untuk menyimpan password asli
        'alamat',
        'tanggal_lahir',
        'status',
        'nominal_spp',

        // Data pribadi
        'jenis_kelamin',
        'agama',
        'tempat_tinggal',
        'moda_transportasi',

        // Data Ayah
        'nama_ayah',
        'nik_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',

        // Data Ibu
        'nama_ibu',
        'nik_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',

        // Kontak
        'no_telp_rumah',
        'no_hp',
        'email',
    ];

    protected $hidden = [
        'password',        // tetap disembunyikan
        'remember_token',  // tetap disembunyikan
        // ❌ password_raw sengaja tidak disembunyikan, supaya bisa ikut export
    ];

    protected $casts = [
        'tanggal_lahir'    => 'date',
        'nominal_spp'      => 'float',
        'penghasilan_ayah' => 'float',
        'penghasilan_ibu'  => 'float',
    ];

    // ✅ Mutator: selalu hash password yang diset
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash((string) $value)
                ? Hash::make((string) $value)
                : $value;
        }
    }

    // ✅ Relasi ke tabel kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    // ✅ Relasi ke tabel sekolah
    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah');
    }

    // ✅ Relasi ke tabel tahun_ajaran
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
    
    public function tagihanAktif()
    {
        return $this->hasMany(Tagihan::class, 'siswa_id')
            ->where('status', 'belum')
            ->where('is_active', true);
    }

    // ✅ Relasi ke tabel tagihan
    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'siswa_id');
    }

    // ✅ Relasi many-to-many ke JenisPembayaran
    public function jenisPembayaran()
    {
        return $this->belongsToMany(JenisPembayaran::class, 'jenis_pembayaran_siswa', 'siswa_id', 'jenis_pembayaran_id')
                    ->withTimestamps();
    }
}
