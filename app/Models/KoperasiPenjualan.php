<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoperasiPenjualan extends Model
{
    use HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'koperasi_penjualan';

    protected $fillable = [
        'sekolah_id',
        'siswa_id',
        'kode_transaksi',
        'tanggal',
        'total',
        'catatan',
        'metode_bayar',
        'wallet_payment_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($sale) {
            $sale->posted_by ??= auth('web')->id();
            $sale->posted_by_name ??= auth('web')->user()?->nama_admin;
        });
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function details()
    {
        return $this->hasMany(KoperasiPenjualanDetail::class, 'koperasi_penjualan_id');
    }

    public function walletPayment()
    {
        return $this->belongsTo(WalletPayment::class, 'wallet_payment_id');
    }
}
