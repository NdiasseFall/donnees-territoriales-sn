<?php

declare(strict_types=1);

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;

// LARAVEL_START peut déjà être défini par artisan.php ou public/index.php.
if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Bootstrap minimal de l'application Laravel, requis pour que la suite de
| tests PHPUnit puisse démarrer le conteneur applicatif.
|
*/

// NB : `require` (et non `require_once`) — chaque bootstrap doit créer une
// nouvelle instance Application (les tests CLI créent l'app par test).
$app = require __DIR__.'/../app.php';

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
*/

$app->singleton(
    Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;
