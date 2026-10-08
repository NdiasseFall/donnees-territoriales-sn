<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Traitement commun à tous les tests : démarrage du conteneur Laravel.
 *
 * US-021 : les tests d'API ne doivent jamais être rate-limités (429) —
 * le throttle est désactivé en environnement testing.
 */
trait CreatesApplication
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Désactive le rate-limiting « api » en tests : chaque test part
        // de la même IP (127.0.0.1) et la suite complète dépasse 30 req/min.
        RateLimiter::for('api', fn () => Limit::none());

        return $app;
    }
}
