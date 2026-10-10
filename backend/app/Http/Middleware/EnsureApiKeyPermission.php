<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Entities\ApiKey;
use App\Http\Responses\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle d'accès basé sur les permissions de la clé API — matrice RBAC §55.
 *
 * Usage : `->middleware('api.key.permission:administer')`.
 * Refuse (403, enveloppe §50) l'accès si la clé active ne porte pas la
 * permission requise. Une absence de clé est également refusée : les routes
 * administrées ne sont jamais publiques.
 */
class EnsureApiKeyPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey instanceof ApiKey) {
            return ApiError::make(
                'FORBIDDEN',
                "Accès refusé : la permission « {$permission} » est requise.",
                403
            );
        }

        if (! $apiKey->hasPermission($permission)) {
            return ApiError::make(
                'INSUFFICIENT_PERMISSIONS',
                "Permission « {$permission} » manquante pour cette ressource.",
                403
            );
        }

        return $next($request);
    }
}
