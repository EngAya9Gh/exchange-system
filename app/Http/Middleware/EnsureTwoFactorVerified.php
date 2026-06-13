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

            // Check if user has 2FA enabled and has not verified in the current session
            if ($user->two_factor_enabled && !session()->has('2fa_verified')) {
                // Allow request to OTP verification page and logout
                if (!$request->routeIs('otp.verify') && !$request->routeIs('logout') && !$request->is('livewire/*')) {
                    return redirect()->route('otp.verify');
                }
            }
        }

        return $next($request);
    }
}
