<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    protected $table = 'tagihan';
    
    protected $fillable = [
        'siswa_id',
        'id_sekolah',
        'jenis_pembayaran_id',
        'tahun_ajaran_id',
        'nama_tagihan',
        'nominal',
        'tipe',
        'periode',
        'tanggal_jatuh_tempo',
        'status',
        'is_active',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_jatuh_tempo' => 'date',
        'is_active' => 'boolean',
    ];

    // Relasi
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jenisPembayaran(): BelongsTo
    {
        return $this->belongsTo(JenisPembayaran::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    // Accessor untuk nama tagihan yang dinamis
    public function getNamaTagihanDinamisAttribute(): string
    {
        if ($this->jenis_pembayaran_id === null) {
            // Untuk SPP
            return 'SPP Bulan ' . $this->periode;
        }
        
        // Untuk jenis pembayaran lain, ambil dari relasi
        return $this->jenisPembayaran
            ? $this->jenisPembayaran->nama_dengan_tahun
            : $this->nama_tagihan;
    }

    // Accessor untuk total yang sudah dibayar
    public function getTotalBayarAttribute(): float
    {
        return $this->pembayaran()->sum('jumlah_bayar');
    }

    // Accessor untuk sisa yang harus dibayar
    public function getSisaBayarAttribute(): float
    {
        return $this->nominal - $this->total_bayar;
    }

    // Accessor untuk persentase pembayaran
    public function getPersentaseBayarAttribute(): float
    {
        if ($this->nominal <= 0) return 0;
        return ($this->total_bayar / $this->nominal) * 100;
    }

    // Accessor untuk status cicilan (khusus bulanan)
    public function getStatusCicilanAttribute(): string
    {
        if ($this->tipe !== 'bulanan' || $this->status === 'lunas') {
            return '';
        }

        $pembayaranTerakhir = $this->pembayaran()->orderByDesc('cicilan_ke')->first();
        if (!$pembayaranTerakhir) {
            return 'Belum ada pembayaran';
        }

        $sisaCicilan = $pembayaranTerakhir->total_cicilan - $pembayaranTerakhir->cicilan_ke;
        return "Cicilan ke-{$pembayaranTerakhir->cicilan_ke} dari {$pembayaranTerakhir->total_cicilan} ({$sisaCicilan} cicilan lagi)";
    }

    // Scope
    public function scopeBelumLunas($query)
    {
        return $query->where('status', 'belum');
    }

    public function scopeLunas($query)
    {
        return $query->where('status', 'lunas');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isBelumJatuhTempo(): bool
    {
        if (!$this->tanggal_jatuh_tempo) {
            return false;
        }

        $dueDate = Carbon::parse($this->tanggal_jatuh_tempo)->toDateString();
        $todayInJambi = Carbon::today('Asia/Jakarta')->toDateString();

        return $dueDate > $todayInJambi;
    }

    public function getSisaCicilanAttribute()
    {
        if ($this->tipe !== 'bulanan') {
            return 0;
        }
        $totalBulanDibayar = $this->pembayaran()->distinct('periode')->count('periode');
        return max(0, 12 - $totalBulanDibayar);
    }
}
