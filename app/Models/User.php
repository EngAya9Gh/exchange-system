<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_enabled',
        'is_active',
        'language',
        'telegram_chat_id',
        'telegram_link_token',
        'balance',
        // Credit: were missing here, so the user screen silently dropped them (fixed 2026-11-18)
        'has_unlimited_balance',
        'balance_limit',
        // Special Vodafone Cash rate (see customVodafoneRate*)
        'custom_vodafone_rate',
        'custom_vodafone_rate_base',
        'custom_vodafone_rate_reviewed_at',
        // Partner API (the key itself is set only through issueApiToken())
        'api_enabled',
        'api_allowed_ips',
    ];

    /** Vodafone Cash transfers: Turkish lira → Egyptian pound */
    public const VODAFONE_RATE_FROM = 'TRY';
    public const VODAFONE_RATE_TO = 'EGP';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'api_token_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
            'balance' => 'decimal:3',
            'has_unlimited_balance' => 'boolean',
            'balance_limit' => 'decimal:2',
            'custom_vodafone_rate' => 'decimal:5',
            'custom_vodafone_rate_base' => 'decimal:5',
            'custom_vodafone_rate_reviewed_at' => 'datetime',
            'api_enabled' => 'boolean',
            'api_last_used_at' => 'datetime',
        ];
    }

    /**
     * New partner API key for this account (the old one stops working). Returned once, stored hashed.
     */
    public function issueApiToken(): string
    {
        $token = 'vc_' . \Illuminate\Support\Str::random(48);

        $this->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'api_token_hint' => substr($token, -4),
            'api_enabled' => true,
        ])->save();

        return $token;
    }

    public function revokeApiToken(): void
    {
        $this->forceFill(['api_token_hash' => null, 'api_token_hint' => null, 'api_enabled' => false])->save();
    }

    public function hasApiToken(): bool
    {
        return filled($this->api_token_hash);
    }

    /** The active account behind a partner API key, or null. */
    public static function findByApiToken(?string $token): ?self
    {
        if (!is_string($token) || !str_starts_with($token, 'vc_') || strlen($token) > 100) {
            return null;
        }

        return static::where('api_token_hash', hash('sha256', $token))
            ->where('api_enabled', true)
            ->where('is_active', true)
            ->first();
    }

    /** IP whitelist (one per line or comma separated); empty = any IP. */
    public function apiIpAllowed(?string $ip): bool
    {
        $allowed = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $this->api_allowed_ips)));

        return $allowed === [] || in_array((string) $ip, $allowed, true);
    }

    /** A fixed TRY → EGP rate set by the admin for this account (it does not follow the general rate). */
    public function hasCustomVodafoneRate(): bool
    {
        return $this->custom_vodafone_rate !== null && (float) $this->custom_vodafone_rate > 0;
    }

    /**
     * The general rate moved since this special rate was set or last reviewed:
     * the admin is reminded to look at it again (the special rate itself never changes on its own).
     */
    public function customVodafoneRateNeedsReview(float $generalRate): bool
    {
        if (!$this->hasCustomVodafoneRate()) {
            return false;
        }

        return $this->custom_vodafone_rate_base === null
            || abs((float) $this->custom_vodafone_rate_base - $generalRate) > 0.000001;
    }

    /** Marks the special rate as reviewed against the current general rate (no reminder until it moves again). */
    public function markCustomVodafoneRateReviewed(float $generalRate): void
    {
        $this->forceFill([
            'custom_vodafone_rate_base' => $generalRate,
            'custom_vodafone_rate_reviewed_at' => now(),
        ])->save();
    }

    public function scopeWithCustomVodafoneRate($query)
    {
        return $query->whereNotNull('custom_vodafone_rate')->where('custom_vodafone_rate', '>', 0);
    }

    /**
     * Accounts whose special rate was set on a different general rate than today's.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function customVodafoneRatesToReview(float $generalRate): \Illuminate\Support\Collection
    {
        return static::withCustomVodafoneRate()
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->customVodafoneRateNeedsReview($generalRate))
            ->values();
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'user_id');
    }

    public function createdTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'created_by');
    }

    public function paidTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'paid_by');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
