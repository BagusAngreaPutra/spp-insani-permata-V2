<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Koperasi extends Model
{
    use HasFactory;

    protected $table = 'koperasi';

    protected $fillable = [
        'sekolah_id',
        'kode_barang',
        'nama_barang',
        'kategori',
        'jenis_transaksi',
        'satuan',
        'harga_beli',
        'harga_jual',
        'harga_sewa_siswa',
        'harga_sewa_umum',
        'harga_penggantian',
        'stok',
        'stok_minimum',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'harga_sewa_siswa' => 'decimal:2',
        'harga_sewa_umum' => 'decimal:2',
        'harga_penggantian' => 'decimal:2',
        'stok' => 'integer',
        'stok_minimum' => 'integer',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function detailPenjualan()
    {
        return $this->hasMany(KoperasiPenjualanDetail::class, 'koperasi_id');
    }

    public function getStatusStokAttribute()
    {
        if ($this->stok <= 0) {
            return 'habis';
        }

        if ($this->stok <= $this->stok_minimum) {
            return 'menipis';
        }

        return 'aman';
    }
}
