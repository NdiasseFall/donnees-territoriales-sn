<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suppression des espaces en début/fin de chaîne sur les entrées requête.
 * Les exceptions correspondent aux champs sensibles à l'authentification.
 */
class TrimStrings
{
    /**
     * Champs exclus du trim.
     *
     * @var list<string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->all() as $key => $value) {
            if (is_string($value) && ! in_array($key, $this->except, true)) {
                $request->request->set($key, trim($value));
            }
        }

        return $next($request);
    }
}
