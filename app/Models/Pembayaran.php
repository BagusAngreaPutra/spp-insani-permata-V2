<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'sekolah_id',
        'tagihan_id',
        'siswa_id',
        'jumlah_bayar',
        'diskon',  // ✅ Sudah ada
        'tanggal_bayar',
        'tanggal_kwitansi',
        'keterangan',
        'periode',
        'periode_tahun',
        'cicilan_ke',
        'total_cicilan',
        'metode_bayar',
        'bukti_bayar',
        'nomor_kwitansi',
        'transaction_id' // ✅ Tambahkan transaction_id
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'diskon' => 'decimal:2',        // ✅ TAMBAH INI
        'tanggal_bayar' => 'date',
        'tanggal_kwitansi' => 'date',
        'cicilan_ke' => 'integer',
        'total_cicilan' => 'integer'
    ];

    // Relasi
    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    // ✅ TAMBAH ACCESSOR UNTUK DISKON
    public function getDiskonAttribute($value)
    {
        return $value ?? 0;
    }

    // Accessor
    public function getTotalBayarAttribute(): float
    {
        return Pembayaran::where('tagihan_id', $this->tagihan_id)->sum('jumlah_bayar');
    }

    public function getSisaCicilanAttribute(): int
    {
        $maxCicilan = Pembayaran::where('tagihan_id', $this->tagihan_id)->max('cicilan_ke') ?? 0;
        return max(0, $this->total_cicilan - $maxCicilan);
    }

    // Scope
    public function scopeByTagihan($query, $tagihanId)
    {
        return $query->where('tagihan_id', $tagihanId);
    }

    public function scopeBySiswa($query, $siswaId)
    {
        return $query->where('siswa_id', $siswaId);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pembayaran) {
            $pembayaran->tanggal_kwitansi ??= $pembayaran->tanggal_bayar;

            // Jika nomor_kwitansi belum diatur
            if (!$pembayaran->nomor_kwitansi) {
                $siswa = \App\Models\Siswa::find($pembayaran->siswa_id);
                
                if ($siswa && $siswa->sekolah && $siswa->sekolah->kode_sekolah) {
                    // Jika ini bagian dari transaksi multi-pembayaran (ada transaction_id)
                    if (!empty($pembayaran->transaction_id)) {
                        // Cek apakah sudah ada pembayaran dengan transaction_id ini
                        $existingPayment = static::where('transaction_id', $pembayaran->transaction_id)
                            ->whereNotNull('nomor_kwitansi')
                            ->first();
                            
                        if ($existingPayment) {
                            // Gunakan nomor kwitansi yang sama
                            $pembayaran->nomor_kwitansi = $existingPayment->nomor_kwitansi;
                        } else {
                            // Buat nomor kwitansi baru
                            $pembayaran->nomor_kwitansi = $siswa->sekolah->getNextNomorKwitansi();
                        }
                    } else {
                        // Untuk pembayaran tunggal, buat nomor kwitansi baru
                        $pembayaran->nomor_kwitansi = $siswa->sekolah->getNextNomorKwitansi();
                    }
                }
            }
        });
    }

}
