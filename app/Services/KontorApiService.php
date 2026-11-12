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
        return Cache::remember(self::BALANCE_CACHE_KEY, $seconds, fn () => $this->balance());
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
