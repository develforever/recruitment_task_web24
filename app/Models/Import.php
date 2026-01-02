<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    const STATUS_SUCCESS = 'success';
    const STATUS_PARTIAL = 'partial';
    const STATUS_FAILED = 'failed';
    const STATUS_PROCESSING = 'processing';

    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
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
