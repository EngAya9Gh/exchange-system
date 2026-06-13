<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'request_id',
        'sender_name',
        'sender_phone',
        'recipient_name',
        'recipient_phone',
        'destination',
        'address',
        'notes',
        'source_amount',
        'source_currency',
        'target_currency',
        'exchange_rate',
        'received_amount',
        'commission',
        'net_amount',
        'secret_code',
        'status',
        'created_by',
        'paid_by',
        'transferred_at',
        'delivered_at'
    ];

    protected $casts = [
        'source_amount' => 'decimal:3',
        'exchange_rate' => 'decimal:5',
        'received_amount' => 'decimal:3',
        'commission' => 'decimal:3',
        'net_amount' => 'decimal:3',
        'transferred_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TransferRequest::class, 'request_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
