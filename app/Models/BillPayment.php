<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'kurum_id',
        'abone_no',
        'fatura_no',
        'amount',
        'api_cost',
        'commission',
        'total_deducted',
        'tahsilat_api_islem_id',
        'api_status',
        'api_status_message',
        'paid_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'api_cost' => 'decimal:3',
        'commission' => 'decimal:3',
        'total_deducted' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
