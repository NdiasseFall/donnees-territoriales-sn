<?php

/*
|--------------------------------------------------------------------------
| Point d'entrée HTTP — backend/public/index.php
|--------------------------------------------------------------------------
*/

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance : chargement du script de maintenance si présent.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader Composer.
require __DIR__.'/../vendor/autoload.php';

// Bootstrap de l'application.
$app = require_once __DIR__.'/../bootstrap/app.php';

// Traitement de la requête.
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
