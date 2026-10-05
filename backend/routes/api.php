<?php

use App\Http\Controllers\Api\V1\Auth\ApiKeyController;
use App\Http\Controllers\Api\V1\DatasetController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SpatialController;
use App\Http\Controllers\Api\V1\TerritoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Plateforme Nationale de Données Territoriales du Sénégal
|--------------------------------------------------------------------------
| Versioning strict v1, format JSON & GeoJSON (RFC 7946), PostGIS EPSG:4326.
*/

Route::prefix('v1')->group(function () {

    // Healthcheck & Métriques système (public, hors authentification)
    Route::get('/health', [HealthCheckController::class, 'health'])->name('api.v1.health');

    // Authentification / Gestion des clés API (Sanctum + clé API requise)
    Route::middleware(['auth:sanctum', 'api.key.active', 'throttle:api.key'])
        ->prefix('auth')->group(function () {
            Route::post('/keys', [ApiKeyController::class, 'store'])->name('api.v1.auth.keys.store');
            Route::get('/keys', [ApiKeyController::class, 'index'])->name('api.v1.auth.keys.index');
            Route::delete('/keys/{uuid}', [ApiKeyController::class, 'destroy'])->name('api.v1.auth.keys.destroy');
            Route::post('/keys/{uuid}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api.v1.auth.keys.regenerate');
        });

    // Territoires administratifs
    Route::prefix('territories')->group(function () {
        Route::get('/', [TerritoryController::class, 'index'])->name('api.v1.territories.index');
        Route::get('/regions', [TerritoryController::class, 'regions'])->name('api.v1.territories.regions');
        Route::get('/departments', [TerritoryController::class, 'departments'])->name('api.v1.territories.departments');
        Route::get('/communes', [TerritoryController::class, 'communes'])->name('api.v1.territories.communes');
        Route::get('/{code}', [TerritoryController::class, 'show'])->name('api.v1.territories.show');
        Route::get('/{code}/hierarchy', [TerritoryController::class, 'hierarchy'])->name('api.v1.territories.hierarchy');
    });

    // Opérations géospatiales PostGIS
    Route::prefix('spatial')->group(function () {
        Route::get('/reverse-geocode', [SpatialController::class, 'reverseGeocode'])->name('api.v1.spatial.reverse_geocode');
        Route::get('/bbox', [SpatialController::class, 'bbox'])->name('api.v1.spatial.bbox');
        Route::post('/intersect', [SpatialController::class, 'intersect'])->name('api.v1.spatial.intersect');
    });

    // Moteur de recherche unifié (Full-text + Trigrammes)
    Route::get('/search', [SearchController::class, 'search'])->name('api.v1.search');

    // Catalogue des jeux de données
    Route::prefix('datasets')->group(function () {
        Route::get('/', [DatasetController::class, 'index'])->name('api.v1.datasets.index');
        Route::get('/{slug}', [DatasetController::class, 'show'])->name('api.v1.datasets.show');
    });

});
