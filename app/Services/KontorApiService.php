<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the kontor86.com dealer API (ZNET Yazılım, see https://bayi.kontor86.com/api.pdf).
 *
 * Every call needs the dealer's API permission ("api yetkisi") and must come from the
 * static server IP registered with kontor86; otherwise the API answers
 * "NOK|API_BAGLANTI_HATASI" (or an empty body).
 *
 * Credentials: saved from the admin screen ("ربط kontor86", Settings table) win over .env.
 */
class KontorApiService
{
    /** tl_servis "Sonuç Durum" values */
    public const SEND_ACCEPTED = 1; // Talebiniz işleme alınmıştır
    public const SEND_FAILED = 3;   // definite failure (wrong credentials, same number+amount within 10 min, …)
    public const SEND_CHECK = 8;    // already sent / outcome unknown → must be checked with checkTopUp()

    public const SETTING_DEALER_CODE = 'KONTOR_DEALER_CODE';
    public const SETTING_PASSWORD = 'KONTOR_API_PASSWORD';
    public const BALANCE_CACHE_KEY = 'kontor86_balance';

    public function dealerCode(): ?string
    {
        return Setting::where('key', self::SETTING_DEALER_CODE)->value('value') ?: config('services.kontor.dealer_code');
    }

    public function password(): ?string
    {
        return Setting::where('key', self::SETTING_PASSWORD)->value('value') ?: config('services.kontor.password');
    }

    public function isConfigured(): bool
    {
        return filled($this->dealerCode()) && filled($this->password());
    }

    /**
     * Our balance at kontor86 (BAKİYE KONTROL). "OK|12755.23"
     *
     * @return array{ok: bool, balance: ?float, error: ?string}
     */
    public function balance(): array
    {
        $raw = $this->call('bakiye_kontrol.php', [
            'kod' => $this->dealerCode(),
            'sifre' => $this->password(),
        ]);

        if ($raw !== null && preg_match('/^OK\|(-?[\d.]+)$/', $raw, $m)) {
            return ['ok' => true, 'balance' => (float) $m[1], 'error' => null];
        }

        return ['ok' => false, 'balance' => null, 'error' => $this->errorCode($raw)];
    }

    /** Same as balance(), cached briefly so screens don't hit the API on every render. */
    public function cachedBalance(int $seconds = 60): array
    {
        return Cache::remember(self::BALANCE_CACHE_KEY, $seconds, fn () => $this->balance());
    }

    /**
     * Load credit / a package on a line (KONTOR GÖNDERİMİ). "OK|1|Talebiniz İşleme Alınmıştır.|5.50"
     *
     * $uniqueId must be unique per order (our order number): resending the same id answers 8.
     * If the request times out we do NOT know whether it was loaded, so the result is
     * SEND_CHECK (never "failed") — the caller must check before refunding or retrying.
     *
     * @return array{ok: bool, status: ?int, message: string, cost: ?float}
     */
    public function sendTopUp(string $operator, string $type, string $amount, string $gsmNo, string $uniqueId): array
    {
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

        if (preg_match('/^OK\|(\d+)\|(.*)\|(-?[\d.]+)$/s', $raw, $m)) {
            $status = (int) $m[1];

            return [
                'ok' => $status === self::SEND_ACCEPTED,
                'status' => $status,
                'message' => trim($m[2]),
                'cost' => (float) $m[3],
            ];
        }

        return ['ok' => false, 'status' => self::SEND_FAILED, 'message' => $this->errorCode($raw), 'cost' => null];
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

            $cost = preg_match('/:(-?[\d.]+)$/', $rest, $c) ? (float) $c[1] : null;

            return ['state' => $code === 1 ? 'success' : 'processing', 'cost' => $cost, 'reason' => null];
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

    /** Raw trimmed body, or null when the request itself failed (timeout, DNS, HTTP error). */
    protected function call(string $endpoint, array $params): ?string
    {
        $url = rtrim((string) config('services.kontor.base_url'), '/') . '/' . $endpoint;
        $safeParams = array_merge($params, ['sifre' => '***']);

        try {
            $response = Http::timeout((int) config('services.kontor.timeout', 30))->get($url, $params);
        } catch (\Throwable $e) {
            Log::error("Kontor86 API [{$endpoint}] exception: " . $e->getMessage(), $safeParams);
            return null;
        }

        if (!$response->successful()) {
            Log::error("Kontor86 API [{$endpoint}] HTTP " . $response->status(), $safeParams);
            return null;
        }

        $body = trim($response->body());
        Log::info("Kontor86 API [{$endpoint}] → " . mb_substr($body, 0, 200), $safeParams);

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
