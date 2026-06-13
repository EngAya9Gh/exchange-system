<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransferRequest extends Model
{
    protected $fillable = [
        'user_id',
        'sender_name',
        'sender_phone',
        'recipient_name',
        'recipient_phone',
        'amount',
        'currency',
        'status',
        'admin_notes'
    ];

    protected $casts = [
        'amount' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transfer(): HasOne
    {
        return $this->hasOne(Transfer::class, 'request_id');
    }
}
