<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * @use HasFactory<TransactionFactory>
     */
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
