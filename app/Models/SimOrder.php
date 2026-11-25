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

    /** Automatic loading through kontor86 (null = manual, loaded by the team from the WhatsApp group) */
    public const API_PROCESSING = 'processing'; // accepted by the supplier, being loaded
    public const API_SUCCESS = 'success';       // loaded
    public const API_FAILED = 'failed';         // refused / cancelled → refunded
    public const API_UNKNOWN = 'unknown';       // outcome not known yet → must be checked, never refunded blindly
    public const API_OPEN_STATES = [self::API_PROCESSING, self::API_UNKNOWN];

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
        'api_status',
        'api_supplier',
        'api_supplier_forced',
        'api_unique_id',
        'api_cost',
        'api_message',
        'api_note',
        'api_checked_at',
        'client_reference',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'charged_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'api_cost' => 'decimal:2',
        'api_checked_at' => 'datetime',
        'api_supplier_forced' => 'boolean',
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

    /** Supplier's answer meaning OUR balance at kontor86 is too low ("Bakiyeniz Yeterli Değildir") */
    public static function isSupplierBalanceProblem(?string $message): bool
    {
        return (bool) preg_match('/bakiye\S*\s+yeterli\s+de[gğ]il/iu', (string) $message);
    }

    /**
     * The product key (operator + tip + code) is not an active product on our account:
     * "Aktif Kontor callbackesim45242". Our configuration problem, never the buyer's.
     */
    public static function isSupplierProductProblem(?string $message): bool
    {
        return (bool) preg_match('/^\s*aktif\s+kontor\b/iu', (string) $message);
    }

    /** Status for the partner API: pending (being loaded) / success / failed (refunded). */
    public function partnerStatus(): string
    {
        return match (true) {
            $this->status === self::STATUS_REJECTED => 'failed',
            $this->status === self::STATUS_PENDING, in_array($this->api_status, self::API_OPEN_STATES, true) => 'pending',
            default => 'success',
        };
    }

    /** The order as the partner API returns it (no supplier names, costs or internal notes). */
    public function toPartnerArray(): array
    {
        $status = $this->partnerStatus();

        return [
            'order_number' => $this->order_number,
            'reference' => $this->client_reference,
            'status' => $status,
            'package_id' => $this->sim_package_id,
            'package_name_ar' => $this->package_name_ar,
            'package_name_tr' => $this->package_name_tr,
            'phone' => $this->phone_number,
            'price' => (float) $this->price,
            'charged' => $status === 'failed' ? 0.0 : (float) $this->charged_amount,
            'failure_reason' => $status === 'failed' ? ($this->admin_notes ?: null) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Suppliers that refused this order for their own reasons before it was sent elsewhere.
     *
     * @return array<string, string> supplier => reason
     */
    public function apiRefusals(): array
    {
        $decoded = json_decode((string) $this->api_note, true);

        return is_array($decoded) ? array_map('strval', $decoded) : [];
    }

    /** The supplier rejected our account (no API permission / wrong password / no answer body). */
    public static function isSupplierAccountProblem(?string $message): bool
    {
        return (bool) preg_match('/API_BAGLANTI_HATASI|EMPTY_RESPONSE|bayi\s*kodu\s+ya\s*da\s+[sş]ifre|api\s+yetkisi/iu', (string) $message);
    }

    /**
     * A refusal caused by the supplier account, not by this order (balance, inactive product, credentials):
     * nothing was loaded or charged there, so the order can be tried at the next supplier.
     */
    public static function isSupplierSideProblem(?string $message): bool
    {
        return static::isSupplierBalanceProblem($message)
            || static::isSupplierProductProblem($message)
            || static::isSupplierAccountProblem($message);
    }

    /**
     * The supplier's reason, safe to show to the buyer: our balance / cost are never shown
     * ("Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35 AL=220" → "service temporarily unavailable").
     */
    public static function publicSupplierReason(?string $message): string
    {
        if (static::isSupplierBalanceProblem($message) || static::isSupplierProductProblem($message)) {
            return __('messages.sim_api_supplier_unavailable');
        }

        $clean = trim((string) preg_replace('/\s*-?\s*(BAK|AL)=[\d.,]+/iu', '', (string) $message));

        return $clean !== '' ? $clean : '-';
    }

    public function isSentByApi(): bool
    {
        return $this->api_status !== null;
    }

    /** Sale price minus the supplier's real cost (when the API told us the cost). */
    public function profit(): ?float
    {
        return $this->api_cost !== null && (float) $this->api_cost > 0 && (float) $this->charged_amount > 0
            ? (float) $this->charged_amount - (float) $this->api_cost
            : null;
    }

    public function getLocalizedPackageNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->package_name_ar : $this->package_name_tr;
    }
}
