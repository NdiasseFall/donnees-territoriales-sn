<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Fournisseur d'application : définition des rate limiters (CDC §53).
 *
 * - `api`       : endpoints publics — 30 req/min/IP.
 * - `api.key`   : endpoints authentifiés par clé — plafond du tier de la clé
 *                 (developer 600/min, public 60/min, admin illimité via
 *                 RateLimitByApiKeyTier + ce garde-fou nommé).
 */
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('api.key', function (Request $request) {
            $apiKey = $request->attributes->get('api_key');

            if ($apiKey === null) {
                return Limit::perMinute(60)->by($request->ip());
            }

            $limit = $apiKey->getTier()->getRateLimit();

            // Tier « unlimited » (0) : aucun plafond imposé ici.
            if ($limit === 0) {
                return Limit::none();
            }

            return Limit::perMinute($limit)->by($apiKey->getUuid());
        });
    }
}
