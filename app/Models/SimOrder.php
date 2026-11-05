<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimOrder extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_REJECTED];

    protected $fillable = [
        'order_number',
        'user_id',
        'sim_package_id',
        'package_name_ar',
        'package_name_tr',
        'region',
        'sim_type',
        'network_name',
        'price',
        'charged_amount',
        'phone_number',
        'customer_name',
        'notes',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'charged_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SimOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /** e.g. SIM2611054821 */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'SIM' . now()->format('ymd') . random_int(1000, 9999);
        } while (static::withTrashed()->where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SimPackage::class, 'sim_package_id')->withTrashed();
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isEsim(): bool
    {
        return $this->sim_type === SimPackage::TYPE_ESIM;
    }

    /**
     * The number as typed on the supplier panel: for physical lines only the 8 digits
     * after "50" (5051234567 → 51234567); other numbers are returned unchanged.
     */
    public function supplierPanelNumber(): string
    {
        $prefix = SimPackage::PHYSICAL_PHONE_PREFIX;

        if (!$this->isEsim() && preg_match('/^' . $prefix . '(\d{8})$/', (string) $this->phone_number, $m)) {
            return $m[1];
        }

        return (string) $this->phone_number;
    }

    public function getLocalizedPackageNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->package_name_ar : $this->package_name_tr;
    }
}
