<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status', // 'processing'|'success'|'partial'|'failed'
    ];

    protected $casts = [
        'total_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}