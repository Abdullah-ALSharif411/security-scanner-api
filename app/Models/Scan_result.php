<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanResult extends Model
{
    protected $fillable = [
        'scan_id',
        'xss_result',
        'sql_result',
        'headers_result',
        'ai_analysis',
    ];

    protected $casts = [
        'scan_id' => 'integer',
    ];

    public function scan()
    {
        return $this->belongsTo(Scan::class);
    }
}
