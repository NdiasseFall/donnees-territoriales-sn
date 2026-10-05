<?php

use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [

    'name' => env('APP_NAME', 'Données Territoriales SN'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'Africa/Dakar',

    'locale' => env('APP_LOCALE', 'fr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fr_SN'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fournisseurs de services enregistrés
    |--------------------------------------------------------------------------
    | Les fournisseurs « default » couvrent le socle Laravel ; les
    | fournisseurs applicatifs sont ajoutés ci-dessous.
    */
    'providers' => ServiceProvider::defaultProviders()->merge([
        AppServiceProvider::class,
        RouteServiceProvider::class,
        RepositoryServiceProvider::class,
        SanctumServiceProvider::class,
        PermissionServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->toArray(),

];
