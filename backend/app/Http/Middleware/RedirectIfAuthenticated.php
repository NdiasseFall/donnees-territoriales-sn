<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige un utilisateur déjà authentifié lorsqu'il tente d'accéder
 * à une page réservée aux invités.
 */
class RedirectIfAuthenticated
{
    /**
     * @param  list<string|null>  ...$guards
     */
    public function handle(Request $request, Closure $next, mixed ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
