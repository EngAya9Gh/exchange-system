<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimPackage extends Model
{
    use SoftDeletes;

    public const REGION_LOCAL = 'local';
    public const REGION_EUROPE = 'europe';
    public const REGIONS = [self::REGION_LOCAL, self::REGION_EUROPE];

    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_ESIM = 'esim';
    public const TYPES = [self::TYPE_PHYSICAL, self::TYPE_ESIM];

    /**
     * Tabs on «اختر الباقة» and «إدارة الباقات»: one per region, and eSIM packages in their own tab
     * so they are never mixed with physical SIMs. Region tabs always show; an eSIM tab only when it has packages.
     */
    public const TABS = ['local', 'local_esim', 'europe', 'europe_esim'];

    /** Colored labels an admin can put on a package card (texts: messages.sim_badge_*, colors: partials/package-badge) */
    public const BADGES = ['best_seller', 'best_value', 'new', 'offer', 'limited'];

    /** Physical (Tele CARD) line numbers always start with this; customers type only the 8 digits after it. */
    public const PHYSICAL_PHONE_PREFIX = '50';

    protected $fillable = [
        'region',
        'sim_type',
        'network_name',
        'data_gb',
        'validity_days',
        'price',
        'name_ar',
        'name_tr',
        'description_ar',
        'description_tr',
        'supplier_code',
        'api_operator',
        'api_tip',
        'api_code',
        'telekomtl_operator',
        'telekomtl_tip',
        'telekomtl_code',
        'is_active',
        'sort_order',
        'badge',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'data_gb' => 'integer',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(SimOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /** Arabic name for Arabic UI, Turkish name otherwise (tr / en). */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_tr;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_tr;
    }

    public function isEsim(): bool
    {
        return $this->sim_type === self::TYPE_ESIM;
    }

    /** "local", "local_esim", "europe", "europe_esim" */
    public function tabKey(): string
    {
        return $this->region . ($this->isEsim() ? '_esim' : '');
    }

    /**
     * @param  \Illuminate\Support\Collection  $packagesByTab  packages grouped by tabKey()
     * @return string[]
     */
    public static function visibleTabs(\Illuminate\Support\Collection $packagesByTab): array
    {
        return array_values(array_filter(
            self::TABS,
            fn (string $tab) => !str_ends_with($tab, '_esim') || $packagesByTab->has($tab)
        ));
    }

    public static function tabFlag(string $tab): string
    {
        return str_starts_with($tab, self::REGION_EUROPE) ? '🇪🇺' : '🇹🇷';
    }

    /** Product key columns (operator, tip, code) per supplier: api_* is kontor86's. */
    public const API_KEY_COLUMNS = [
        'kontor86' => ['api_operator', 'api_tip', 'api_code'],
        'telekomtl' => ['telekomtl_operator', 'telekomtl_tip', 'telekomtl_code'],
    ];

    /** @return array{0: string, 1: string, 2: string}|null operator, tip, code at this supplier (all three set) */
    public function productKey(string $supplier): ?array
    {
        $columns = self::API_KEY_COLUMNS[$supplier] ?? null;
        if ($columns === null) {
            return null;
        }

        $key = array_map(fn (string $column) => (string) $this->{$column}, $columns);

        return in_array('', array_map('trim', $key), true) ? null : $key;
    }

    public function isApiReadyFor(string $supplier): bool
    {
        return $this->productKey($supplier) !== null;
    }

    /** Can be sent automatically to at least one supplier (a complete product key there). */
    public function isApiReady(): bool
    {
        foreach (array_keys(self::API_KEY_COLUMNS) as $supplier) {
            if ($this->isApiReadyFor($supplier)) {
                return true;
            }
        }

        return false;
    }

    /** The supplier refused this key ("Aktif Kontor …"): stop trying it there until an admin re-enters it. */
    public function clearProductKey(string $supplier): void
    {
        $this->update(array_fill_keys(self::API_KEY_COLUMNS[$supplier] ?? [], null));
    }

    /** "30 يوم", "6 أشهر" / "30 Gün", "6 Ay" (whole months when longer than 30 days) */
    public function durationLabel(): string
    {
        $days = (int) $this->validity_days;

        if ($days > 30 && $days % 30 === 0) {
            $months = intdiv($days, 30);
            $key = match (true) {
                $months === 2 => 'sim_duration_two_months',
                $months <= 10 => 'sim_duration_months_few',
                default => 'sim_duration_months_many',
            };

            return __("messages.{$key}", ['count' => $months]);
        }

        return __('messages.sim_duration_days', ['count' => $days]);
    }

    /** 1100 → "1,100", 299.5 → "299.50" */
    public static function formatPrice(float|string|null $amount): string
    {
        $amount = (float) $amount;

        return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    /**
     * Default AR/TR names built from the package attributes,
     * used when the admin leaves the name fields empty.
     *
     * @return array{name_ar: string, name_tr: string}
     */
    public static function generateNames(string $region, string $simType, int $dataGb, int $validityDays): array
    {
        $isEurope = $region === self::REGION_EUROPE;
        $isEsim = $simType === self::TYPE_ESIM;

        return [
            'name_ar' => sprintf(
                'باقة %s %d جيجا - %d يوم (%s)',
                $isEurope ? 'أوروبا' : 'تركيا',
                $dataGb,
                $validityDays,
                $isEsim ? 'eSIM' : 'فيزيائية'
            ),
            'name_tr' => sprintf(
                '%s %d GB - %d Gün (%s)',
                $isEurope ? 'Avrupa' : 'Türkiye',
                $dataGb,
                $validityDays,
                $isEsim ? 'E-SIM' : 'Fiziksel'
            ),
        ];
    }
}
