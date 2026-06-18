<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has 2FA enabled, has not verified in the current session, and didn't use Remember Me
            if ($user->two_factor_enabled && !session()->has('2fa_verified') && !Auth::viaRemember()) {
                // Allow request to OTP verification page, logout, livewire, and public receipt/transfer routes
                if (!$request->routeIs('otp.*') && 
                    !$request->routeIs('logout') && 
                    !$request->routeIs('force-logout') && 
                    !$request->is('livewire/*') &&
                    !$request->routeIs('receipt.*') &&
                    !$request->routeIs('transfers.*')) {
                    return redirect()->route('otp.verify');
                }
            }
        }

        return $next($request);
    }
}
