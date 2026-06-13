<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLogoutOnIdle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_timestamp');
            $idleTimeout = 15 * 60; // 15 minutes in seconds

            if ($lastActivity && (time() - $lastActivity) > $idleTimeout) {
                Auth::logout();
                session()->flush();

                return redirect()->route('login')->with('status', 'تم تسجيل خروجك تلقائياً لعدم النشاط.');
            }

            session(['last_activity_timestamp' => time()]);
        }

        return $next($request);
    }
}
