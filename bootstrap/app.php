<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Telegram webhook — uses 'api' middleware group (no CSRF, no auth, no 2FA, no session)
            Route::middleware('api')
                ->group(base_path('routes/telegram.php'));

            // Partner API v1 (other systems order SIM packages with an API key) — see /api-docs
            Route::middleware('api')
                ->group(base_path('routes/partner_api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // Trust all proxies (Cloudflare, Nginx, cPanel) so APP_URL is auto-detected
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            \App\Http\Middleware\AutoLogoutOnIdle::class,
            \App\Http\Middleware\EnsureTwoFactorVerified::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        ]);

        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => route('login'));
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if (auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Agent'])) {
                return route('admin.dashboard');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The partner API always answers JSON in its own format (404, 405, 429, 500 …)
        $exceptions->shouldRenderJsonWhen(fn (\Illuminate\Http\Request $request) => $request->is('api/*'));
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!$request->is('api/v1/*')) {
                return null;
            }
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
            [$code, $message] = match ($status) {
                404 => ['not_found', 'Unknown endpoint.'],
                405 => ['method_not_allowed', 'Wrong HTTP method for this endpoint.'],
                429 => ['too_many_requests', 'Too many requests: the limit is 60 per minute.'],
                default => ['server_error', 'Unexpected error, please try again.'],
            };

            return response()->json(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], $status);
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect()->back()->withInput($request->except('password'))->with('error', 'انتهت صلاحية الصفحة، يرجى المحاولة مجدداً.');
        });
    })->create();
