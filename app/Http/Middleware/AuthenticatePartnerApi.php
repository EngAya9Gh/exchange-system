<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner API authentication: "Authorization: Bearer vc_…" (or "X-Api-Key: vc_…").
 * The key belongs to one account; every order is charged to that account's balance.
 */
class AuthenticatePartnerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Api-Key');
        $user = User::findByApiToken($token);

        if (!$user) {
            return self::error('unauthorized', 'Missing or invalid API key.', 401);
        }

        if (!$user->apiIpAllowed($request->ip())) {
            return self::error('ip_not_allowed', 'This IP address is not allowed for this API key: ' . $request->ip(), 403);
        }

        if ($user->api_last_used_at === null || $user->api_last_used_at->lt(now()->subMinute())) {
            $user->forceFill(['api_last_used_at' => now()])->saveQuietly();
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    public static function error(string $code, string $message, int $status, array $extra = []): Response
    {
        return response()->json(['ok' => false, 'error' => ['code' => $code, 'message' => $message] + $extra], $status);
    }
}
