<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de rate limiting dynamique basé sur le tier de la clé API.
 * - public: 60 req/min
 * - developer: 600 req/min
 * - admin: unlimited
 */
class RateLimitByApiKeyTier
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if ($apiKey === null) {
            return $next($request);
        }

        $tier = $apiKey->getTier();
        $limit = $tier->getRateLimit();

        if ($limit === 0) {
            return $next($request);
        }

        $prefix = $tier->getRateLimitPrefix();
        $key = $prefix.$apiKey->getUuid();

        // Utilise le rate limiter natif de Laravel
        $maxAttempts = $limit;
        $decayMinutes = 1;

        if (app('cache')->has($key)) {
            $attempts = (int) app('cache')->get($key);
            if ($attempts >= $maxAttempts) {
                return response()->json([
                    'error' => 'Limite de requêtes atteinte pour ce tier.',
                    'retry_after_seconds' => 60,
                ], 429)->header('Retry-After', '60');
            }
            app('cache')->increment($key);
        } else {
            app('cache')->put($key, 1, now()->addMinutes(1));
        }

        return $next($request);
    }
}
