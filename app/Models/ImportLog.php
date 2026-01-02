<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    public $timestamps = false; // created_at is set manually with DB default or in code
    protected $fillable = [
        'import_id',
        'transaction_id',
        'error_message',
        'created_at', // or let DB set default timestamp
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}