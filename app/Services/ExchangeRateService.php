<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExchangeRateService
{
    /**
     * Get the exchange rate between two currencies.
     *
     * @param string $from
     * @param string $to
     * @param int|null $regionId
     * @return float
     */
    public function getRate(string $from, string $to, ?int $regionId = null): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        // 1. Try to find the rate in the database
        $dbRate = ExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->first();

        if ($dbRate) {
            return (float) $dbRate->rate;
        }

        // 2. Hardcoded Fallbacks
        return $this->getFallbackRate($from, $to);
    }

    /**
     * Set a manual exchange rate override.
     */
    public function setRate(string $from, string $to, float $rate, ?int $regionId = null): ExchangeRate
    {
        return ExchangeRate::updateOrCreate(
            [
                'from_currency' => strtoupper($from),
                'to_currency' => strtoupper($to)
            ],
            [
                'rate' => $rate,
                'last_fetched_at' => null // Null indicates a manual override
            ]
        );
    }

    /**
     * Fetch rate from ExchangeRate-API.
     */
    protected function fetchFromApi(string $from, string $to): float
    {
        $apiKey = config('services.exchangerate.api_key');
        
        try {
            $response = Http::timeout(15)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}");
            
            if ($response->successful() && isset($response->json()['conversion_rate'])) {
                return (float) $response->json()['conversion_rate'];
            }
            
            Log::error("ExchangeRate API Error", ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error("ExchangeRate API Exception: " . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Sync all main currencies directly against USD and calculate cross rates.
     */
    public function syncAllRates(): void
    {
        if (!$this->hasApiKey()) {
            return;
        }

        $apiKey = config('services.exchangerate.api_key');
        try {
            $response = Http::timeout(15)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");
            
            if ($response->successful() && $response->json()['result'] === 'success') {
                $rates = $response->json()['conversion_rates'];

                $targets = ['TRY', 'EUR', 'EGP'];
                
                foreach ($targets as $currency) {
                    if (isset($rates[$currency])) {
                        // USD to Currency
                        ExchangeRate::updateOrCreate(
                            ['from_currency' => 'USD', 'to_currency' => $currency],
                            ['rate' => $rates[$currency], 'last_fetched_at' => Carbon::now()]
                        );
                        // Currency to USD
                        ExchangeRate::updateOrCreate(
                            ['from_currency' => $currency, 'to_currency' => 'USD'],
                            ['rate' => 1 / $rates[$currency], 'last_fetched_at' => Carbon::now()]
                        );
                    }
                }

                if (isset($rates['EUR'], $rates['TRY'], $rates['EGP'])) {
                    // EUR to TRY
                    ExchangeRate::updateOrCreate(
                        ['from_currency' => 'EUR', 'to_currency' => 'TRY'],
                        ['rate' => $rates['TRY'] / $rates['EUR'], 'last_fetched_at' => Carbon::now()]
                    );
                    // TRY to EUR
                    ExchangeRate::updateOrCreate(
                        ['from_currency' => 'TRY', 'to_currency' => 'EUR'],
                        ['rate' => $rates['EUR'] / $rates['TRY'], 'last_fetched_at' => Carbon::now()]
                    );
                    
                    // EUR to EGP
                    ExchangeRate::updateOrCreate(
                        ['from_currency' => 'EUR', 'to_currency' => 'EGP'],
                        ['rate' => $rates['EGP'] / $rates['EUR'], 'last_fetched_at' => Carbon::now()]
                    );
                    // TRY to EGP
                    ExchangeRate::updateOrCreate(
                        ['from_currency' => 'TRY', 'to_currency' => 'EGP'],
                        ['rate' => $rates['EGP'] / $rates['TRY'], 'last_fetched_at' => Carbon::now()]
                    );
                }

                Log::info("All exchange rates synced successfully.");
            }
        } catch (\Exception $e) {
            Log::error("ExchangeRate API Sync Exception: " . $e->getMessage());
        }
    }

    /**
     * Check if the API key is configured.
     */
    protected function hasApiKey(): bool
    {
        $key = config('services.exchangerate.api_key');
        return !empty($key) && $key !== 'YOUR_API_KEY';
    }

    /**
     * Get hardcoded fallback rates if DB and API are unavailable.
     */
    protected function getFallbackRate(string $from, string $to): float
    {
        $key = "{$from}_{$to}";

        $fallbacks = [
            'TRY_EGP' => 1.510,
            'EGP_TRY' => 0.662,
            'USD_TRY' => 32.500,
            'TRY_USD' => 0.031,
            'EUR_TRY' => 35.200,
            'TRY_EUR' => 0.028,
            'USD_EGP' => 48.000,
            'EGP_USD' => 0.021,
            'EUR_EGP' => 52.000,
            'EGP_EUR' => 0.019,
        ];

        return $fallbacks[$key] ?? 1.0;
    }
}
