<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiError;
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

        $attempts = (int) app('cache')->get($key, 0);

        if ($attempts >= $maxAttempts) {
            // US-022 (CDC §53) : 429 en enveloppe §50, avec en-têtes standard.
            // Retry-After + X-RateLimit-* (conformes à la doc OpenAPI /api/docs).
            $retryAfter = 60;
            $response = ApiError::make(
                'RATE_LIMIT_EXCEEDED',
                "Limite de requêtes atteinte pour ce tier ({$maxAttempts}/min). Réessayez dans {$retryAfter}s.",
                429
            );
            $response->headers->set('Retry-After', (string) $retryAfter);
            $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', '0');

            return $response;
        }

        if ($attempts === 0) {
            app('cache')->put($key, 1, now()->addMinutes($decayMinutes));
        } else {
            app('cache')->increment($key);
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $maxAttempts - $attempts - 1));

        return $response;
    }
}
