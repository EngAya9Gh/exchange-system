<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'last_fetched_at'
    ];

    protected $casts = [
        'rate' => 'decimal:5',
        'last_fetched_at' => 'datetime',
    ];
}
