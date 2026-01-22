<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'status',
        'pdf_path', // 🔥 مهم جدًا
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];

    public function results()
    {
        return $this->hasOne(ScanResult::class);
    }
}
