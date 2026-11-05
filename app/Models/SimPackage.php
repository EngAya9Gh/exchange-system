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

    protected $fillable = [
        'region',
        'sim_type',
        'data_gb',
        'validity_days',
        'price',
        'name_ar',
        'name_tr',
        'description_ar',
        'description_tr',
        'supplier_code',
        'is_active',
        'sort_order',
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
