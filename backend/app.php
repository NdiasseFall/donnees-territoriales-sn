<?php

declare(strict_types=1);
use Illuminate\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Factory de l'application Laravel
|--------------------------------------------------------------------------
| Située à la racine de backend/ (voir bootstrap/app.php qui la charge).
| Aucune logique applicative ici : uniquement l'instanciation du conteneur
| avec le bon base path.
*/

$app = new Application(__DIR__);

return $app;
