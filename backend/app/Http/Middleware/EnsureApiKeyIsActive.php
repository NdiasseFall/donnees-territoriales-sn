<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Entities\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware garantissant que la clé API attachée à la requête est active et non expirée.
 */
class EnsureApiKeyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (! ($apiKey instanceof ApiKey)) {
            return response()->json([
                'error' => 'Clé API invalide.',
            ], 401);
        }

        if (! $apiKey->isActive()) {
            return response()->json([
                'error' => 'Clé API désactivée.',
            ], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json([
                'error' => 'Clé API expirée.',
            ], 401);
        }

        // Mise à jour du last_used_at
        $request->attributes->set('checked_api_key', $apiKey);

        return $next($request);
    }
}
