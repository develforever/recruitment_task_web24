<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'integer',
    ];
}
