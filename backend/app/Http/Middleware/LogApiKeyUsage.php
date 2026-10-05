<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Entities\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware enregistrant l'utilisation de la clé API pour l'audit.
 */
class LogApiKeyUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $apiKey = $request->attributes->get('checked_api_key');
        if ($apiKey instanceof ApiKey) {
            $request->attributes->set('api_key', $apiKey);
        }

        return $response;
    }
}
