<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{

    use HasFactory;
    protected $fillable = [
        'import_id',
        'transaction_id',
        'error_message',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}