<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'region_id',
        'from_currency',
        'to_currency',
        'rate',
        'last_fetched_at'
    ];

    protected $casts = [
        'rate' => 'decimal:5',
        'last_fetched_at' => 'datetime',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
