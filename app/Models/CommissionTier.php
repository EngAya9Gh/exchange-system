<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionTier extends Model
{
    protected $fillable = [
        'region_id',
        'min_amount',
        'max_amount',
        'commission_type',
        'commission_value',
        'status'
    ];

    protected $casts = [
        'min_amount' => 'decimal:3',
        'max_amount' => 'decimal:3',
        'commission_value' => 'decimal:3',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
