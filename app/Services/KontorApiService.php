<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for a ZNET Yazılım dealer API (see https://bayi.kontor86.com/api.pdf).
 * Two suppliers run the same software, each with its own account: kontor86 (default) and telekomtl.
 * `app(KontorApiService::class)` is kontor86; `KontorApiService::for('telekomtl')` is the other one.
 *
 * Every call needs the dealer's API permission ("api yetkisi"); otherwise the API answers
 * "NOK|API_BAGLANTI_HATASI" (or an empty body).
 *
 * Credentials: saved from the admin screen ("ربط الموردين", Settings table) win over .env.
 */
class KontorApiService
{
    public const SUPPLIER_KONTOR86 = 'kontor86';
    public const SUPPLIER_TELEKOMTL = 'telekomtl';
    public const SUPPLIERS = [self::SUPPLIER_KONTOR86, self::SUPPLIER_TELEKOMTL];

    /** The supplier tried first for every automatic load (the admin picks the cheaper one). */
    public const SETTING_PRIMARY_SUPPLIER = 'SIM_PRIMARY_SUPPLIER';

    /** tl_servis "Sonuç Durum" values */
    public const SEND_ACCEPTED = 1; // Talebiniz işleme alınmıştır
    public const SEND_FAILED = 3;   // definite failure (wrong credentials, same number+amount within 10 min, …)
    public const SEND_CHECK = 8;    // already sent / outcome unknown → must be checked with checkTopUp()

    public const SETTING_DEALER_CODE = 'KONTOR_DEALER_CODE';
    public const SETTING_PASSWORD = 'KONTOR_API_PASSWORD';
    public const BALANCE_CACHE_KEY = 'kontor86_balance';

    /** Emergency switch: while paused, new orders are charged as usual but go to the WhatsApp group only. */
    public const SETTING_AUTO_SEND_PAUSED = 'KONTOR_AUTO_SEND_PAUSED';
    public const SETTING_AUTO_SEND_PAUSED_BY = 'KONTOR_AUTO_SEND_PAUSED_BY';
    public const SETTING_AUTO_SEND_PAUSED_AT = 'KONTOR_AUTO_SEND_PAUSED_AT';

    public function __construct(public readonly string $supplier = self::SUPPLIER_KONTOR86)
    {
        if (!in_array($supplier, self::SUPPLIERS, true)) {
            throw new \InvalidArgumentException("Unknown SIM supplier [{$supplier}].");
        }
    }

    public static function for(string $supplier): self
    {
        return $supplier === self::SUPPLIER_KONTOR86 ? app(self::class) : new self($supplier);
    }

    /**
     * @param  string|null  $first  a package's preferred supplier, tried before the primary one
     * @return self[] keyed by supplier: $first, then the primary one, then the rest
     */
    public static function inPriorityOrder(?string $first = null): array
    {
        $primary = self::primarySupplier();
        $head = in_array($first, self::SUPPLIERS, true) ? [$first, $primary] : [$primary];
        $order = array_values(array_unique(array_merge($head, self::SUPPLIERS)));

        return array_combine($order, array_map(fn (string $s) => self::for($s), $order));
    }

    public static function primarySupplier(): string
    {
        $saved = Setting::where('key', self::SETTING_PRIMARY_SUPPLIER)->value('value');

        return in_array($saved, self::SUPPLIERS, true) ? $saved : self::SUPPLIER_KONTOR86;
    }

    public static function setPrimarySupplier(string $supplier): void
    {
        if (!in_array($supplier, self::SUPPLIERS, true)) {
            throw new \InvalidArgumentException("Unknown SIM supplier [{$supplier}].");
        }
        Setting::updateOrCreate(['key' => self::SETTING_PRIMARY_SUPPLIER], ['value' => $supplier]);
    }

    /**
     * State of every supplier for the admin screens (no API call).
     *
     * @return array<string, array{supplier: string, configured: bool, active: bool, primary: bool,
     *         pause: array{by: ?string, at: ?\Illuminate\Support\Carbon}|null, packages: int}>  primary first
     */
    public static function overview(): array
    {
        $primary = self::primarySupplier();
        $packages = \App\Models\SimPackage::active()->get();
        $state = [];

        foreach (self::inPriorityOrder() as $supplier => $api) {
            $state[$supplier] = [
                'supplier' => $supplier,
                'configured' => $api->isConfigured(),
                'active' => $api->autoSendActive(),
                'primary' => $supplier === $primary,
                'pause' => $api->autoSendPauseInfo(),
                'packages' => $packages->filter(fn ($p) => $p->isApiReadyFor($supplier))->count(),
                // Packages no other supplier carries: while this one is paused they are loaded by hand
                'exclusive' => $packages->filter(fn ($p) => $p->isApiReadyFor($supplier)
                    && collect(self::SUPPLIERS)->reject(fn ($s) => $s === $supplier)->every(fn ($s) => !$p->isApiReadyFor($s)))
                    ->map(fn ($p) => $p->localized_name)->values()->all(),
            ];
        }

        return $state;
    }

    /** "kontor86" / "telekomtl" (shown as is in the UI and the WhatsApp group) */
    public function label(): string
    {
        return $this->supplier;
    }

    /** kontor86 keeps its historical keys (KONTOR_*), the others use their own prefix (TELEKOMTL_*). */
    public function settingKey(string $name): string
    {
        $prefix = $this->supplier === self::SUPPLIER_KONTOR86 ? 'KONTOR_' : strtoupper($this->supplier) . '_';

        return $prefix . $name;
    }

    public function balanceCacheKey(): string
    {
        return $this->supplier === self::SUPPLIER_KONTOR86 ? self::BALANCE_CACHE_KEY : $this->supplier . '_balance';
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        $section = $this->supplier === self::SUPPLIER_KONTOR86 ? 'kontor' : $this->supplier;

        return config("services.{$section}.{$key}", $default);
    }

    public function dealerCode(): ?string
    {
        return Setting::where('key', $this->settingKey('DEALER_CODE'))->value('value') ?: $this->config('dealer_code');
    }

    public function password(): ?string
    {
        return Setting::where('key', $this->settingKey('API_PASSWORD'))->value('value') ?: $this->config('password');
    }

    public function saveCredentials(string $dealerCode, string $password): void
    {
        Setting::updateOrCreate(['key' => $this->settingKey('DEALER_CODE')], ['value' => trim($dealerCode)]);
        Setting::updateOrCreate(['key' => $this->settingKey('API_PASSWORD')], ['value' => trim($password)]);
        Cache::forget($this->balanceCacheKey());
    }

    public function isConfigured(): bool
    {
        return filled($this->dealerCode()) && filled($this->password());
    }

    public function isAutoSendPaused(): bool
    {
        return Setting::where('key', $this->settingKey('AUTO_SEND_PAUSED'))->value('value') === '1';
    }

    /** New orders are sent to kontor86 only when the account is set up and the switch is on. */
    public function autoSendActive(): bool
    {
        return $this->isConfigured() && !$this->isAutoSendPaused();
    }

    /** @return array{by: ?string, at: ?\Illuminate\Support\Carbon}|null who paused it and when (null while running) */
    public function autoSendPauseInfo(): ?array
    {
        if (!$this->isAutoSendPaused()) {
            return null;
        }

        $at = Setting::where('key', $this->settingKey('AUTO_SEND_PAUSED_AT'))->value('value');

        return [
            'by' => Setting::where('key', $this->settingKey('AUTO_SEND_PAUSED_BY'))->value('value') ?: null,
            'at' => $at ? \Illuminate\Support\Carbon::parse($at) : null,
        ];
    }

    public function setAutoSendPaused(bool $paused, ?\App\Models\User $by = null): void
    {
        Setting::updateOrCreate(['key' => $this->settingKey('AUTO_SEND_PAUSED')], ['value' => $paused ? '1' : '0']);
        Setting::updateOrCreate(['key' => $this->settingKey('AUTO_SEND_PAUSED_BY')], ['value' => $paused ? (string) $by?->name : '']);
        Setting::updateOrCreate(['key' => $this->settingKey('AUTO_SEND_PAUSED_AT')], ['value' => $paused ? now()->toDateTimeString() : '']);
    }

    /**
     * Our balance at kontor86 (BAKİYE KONTROL).
     * The PDF documents "OK|12755.23"; the live API answers with extra fields, e.g. "OK|0.00|0.00"
     * (first value = balance; the meaning of the others is not documented).
     *
     * @return array{ok: bool, balance: ?float, error: ?string}
     */
    public function balance(): array
    {
        $raw = $this->call('bakiye_kontrol.php', [
            'kod' => $this->dealerCode(),
            'sifre' => $this->password(),
        ]);

        if ($raw !== null && preg_match('/^OK\|(-?[\d.]+)(\|.*)?$/', $raw, $m)) {
            return ['ok' => true, 'balance' => (float) $m[1], 'error' => null];
        }

        return ['ok' => false, 'balance' => null, 'error' => $this->errorCode($raw)];
    }

    /** Same as balance(), cached briefly so screens don't hit the API on every render. */
    public function cachedBalance(int $seconds = 60): array
    {
        return Cache::remember($this->balanceCacheKey(), $seconds, fn () => $this->balance());
    }

    /**
     * Load credit / a package on a line (KONTOR GÖNDERİMİ). "OK|1|Talebiniz İşleme Alınmıştır.|5.50"
     * PDF: "Cevap Formatı : OK|Sonuç Durum|Açıklama|Maliyet" → the cost is the FIRST number after the
     * description. The live API adds fields (a trailing "|", and possibly the remaining balance after the
     * cost, like its fatura/pin services do), so never take the LAST number: that read our balance (10,545)
     * as the cost. "OK|3|Aktif Kontor turkcell3gcep5|0.00|" = product not active on the account.
     *
     * $uniqueId must be DIGITS ONLY and unique per order: letters make the API answer
     * "8:Api İd Gönderim Hatası" (verified live 2026-11-09); resending the same id answers 8.
     * A timeout or any reply we cannot parse is SEND_CHECK (never "failed") — we do not know
     * whether it was loaded, so the caller must check before refunding or retrying.
     *
     * @return array{ok: bool, status: ?int, message: string, cost: ?float}
     */
    public function sendTopUp(string $operator, string $type, string $amount, string $gsmNo, string $uniqueId): array
    {
        if (!ctype_digit($uniqueId)) {
            throw new \InvalidArgumentException("kontor86 tekilnumara must be digits only, got [{$uniqueId}].");
        }

        $raw = $this->call('tl_servis.php', [
            'bayi_kodu' => $this->dealerCode(),
            'sifre' => $this->password(),
            'operator' => $operator,
            'tip' => $type,
            'kontor' => $amount,
            'gsmno' => $gsmNo,
            'tekilnumara' => $uniqueId,
        ]);

        if ($raw === null) {
            return ['ok' => false, 'status' => self::SEND_CHECK, 'message' => 'NO_RESPONSE', 'cost' => null];
        }

        $fields = explode('|', rtrim($raw, "| \t\n\r"));
        if (count($fields) >= 3 && $fields[0] === 'OK' && ctype_digit(trim($fields[1]))) {
            $status = (int) trim($fields[1]);
            [$message, $numbers] = $this->splitTextAndNumbers(array_slice($fields, 2));

            return [
                'ok' => $status === self::SEND_ACCEPTED,
                'status' => $status,
                'message' => $message !== '' ? $message : '-',
                'cost' => $numbers[0] ?? null,
            ];
        }

        // Explicit rejection of the credentials: nothing can have been processed
        if (str_starts_with($raw, 'NOK|') || $raw === '') {
            return ['ok' => false, 'status' => self::SEND_FAILED, 'message' => $this->errorCode($raw), 'cost' => null];
        }

        // Anything else (e.g. "8:Api İd Gönderim Hatası") is not a documented answer → check, don't assume
        return ['ok' => false, 'status' => self::SEND_CHECK, 'message' => $raw, 'cost' => null];
    }

    /**
     * Status of a load sent with sendTopUp (KONTOR KONTROLÜ).
     * "1:olumlu_islem:5.50" | "2:islemde:5.50" | "3:iptal_nedeni"
     *
     * @return array{state: string, cost: ?float, reason: ?string}  state: success|processing|cancelled|unknown
     */
    public function checkTopUp(string $uniqueId): array
    {
        $raw = $this->call('tl_kontrol.php', [
            'bayi_kodu' => $this->dealerCode(),
            'sifre' => $this->password(),
            'tekilnumara' => $uniqueId,
        ]);

        if ($raw !== null && preg_match('/^([123]):(.*)$/s', $raw, $m)) {
            [$code, $rest] = [(int) $m[1], trim($m[2])];

            if ($code === 3) {
                return ['state' => 'cancelled', 'cost' => null, 'reason' => $rest];
            }

            // "olumlu_islem:5.50" (the number is not described in the PDF; its example equals the send cost)
            [, $numbers] = $this->splitTextAndNumbers(explode(':', $rest));

            return ['state' => $code === 1 ? 'success' : 'processing', 'cost' => $numbers[0] ?? null, 'reason' => null];
        }

        return ['state' => 'unknown', 'cost' => null, 'reason' => $this->errorCode($raw)];
    }

    /**
     * This server's public (outbound) IP — the address kontor86 must register.
     * Null if it could not be determined.
     */
    public function serverPublicIp(): ?string
    {
        try {
            $ip = trim(Http::timeout(8)->get('https://api.ipify.org')->body());

            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ["Talebiniz İşleme Alınmıştır.", "350.00", "10545.00"] → ["Talebiniz İşleme Alınmıştır.", [350.0, 10545.0]]
     * Text = the fields before the first number; numbers = every numeric field after it, in order.
     *
     * @param  string[]  $fields
     * @return array{0: string, 1: float[]}
     */
    protected function splitTextAndNumbers(array $fields): array
    {
        $text = [];
        $numbers = [];

        foreach ($fields as $field) {
            $field = trim($field);
            if (is_numeric($field)) {
                $numbers[] = (float) $field;
            } elseif ($numbers === [] && $field !== '') {
                $text[] = $field;
            }
        }

        return [implode(' | ', $text), $numbers];
    }

    /** Raw trimmed body, or null when the request itself failed (timeout, DNS, HTTP error). */
    protected function call(string $endpoint, array $params): ?string
    {
        $url = rtrim((string) $this->config('base_url'), '/') . '/' . $endpoint;
        $safeParams = array_merge($params, ['sifre' => '***']);
        $tag = ucfirst($this->supplier) . ' API';

        try {
            $response = Http::timeout((int) $this->config('timeout', 30))->get($url, $params);
        } catch (\Throwable $e) {
            Log::error("{$tag} [{$endpoint}] exception: " . $e->getMessage(), $safeParams);
            return null;
        }

        if (!$response->successful()) {
            Log::error("{$tag} [{$endpoint}] HTTP " . $response->status(), $safeParams);
            return null;
        }

        $body = trim($response->body());
        Log::info("{$tag} [{$endpoint}] → " . mb_substr($body, 0, 200), $safeParams);

        return $body;
    }

    protected function errorCode(?string $raw): string
    {
        if ($raw === null) {
            return 'NO_RESPONSE';
        }
        if ($raw === '') {
            return 'EMPTY_RESPONSE'; // dealer not found / API not enabled / IP not registered
        }

        return preg_replace('/^N?OK\|/', '', $raw);
    }
}
