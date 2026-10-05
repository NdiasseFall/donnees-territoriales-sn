<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Chargement des routes HTTP de l'application.
 *
 * routes/api.php est monté sous le préfixe global /api ; le versionnement
 * strict (/api/v1/...) est déclaré dans le fichier de routes lui-même.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
