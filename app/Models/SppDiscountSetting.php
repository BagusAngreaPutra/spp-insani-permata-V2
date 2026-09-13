<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppDiscountSetting extends Model
{
    protected $fillable = [
        'nominal',
        'berlaku_mulai',
        'berlaku_sampai',
        'aktif',
        'updated_by',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
        'aktif' => 'boolean',
    ];
}
