<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\WhatsApp\WhatsAppManager::class, function ($app) {
            return new \App\Services\WhatsApp\WhatsAppManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Partner API: 60 requests per minute per API key. Keyed by the key itself (hashed), not by the user:
        // Laravel runs throttling before our key middleware, so the user is not known yet at that point.
        \Illuminate\Support\Facades\RateLimiter::for('partner-api', function (\Illuminate\Http\Request $request) {
            $key = $request->bearerToken() ?: $request->header('X-Api-Key');

            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by('partner-api:' . ($key ? hash('sha256', (string) $key) : 'ip:' . $request->ip()));
        });

        // Force HTTPS on production/server to fix Livewire JS loading issues
        if (config('app.env') !== 'local' || request()->header('x-forwarded-proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
