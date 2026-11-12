<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    protected $fillable = ['numbering_period', 'show_officer'];
    protected $casts = ['show_officer' => 'boolean'];

    public static function current(): self
    {
        return static::find(1) ?? new static(['numbering_period' => 'yearly', 'show_officer' => false]);
    }
}
