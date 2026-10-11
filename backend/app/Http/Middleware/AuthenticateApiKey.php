<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le jeton porteur (`Authorization: Bearer tnp_...`) en entité ApiKey
 * et la dépose dans l'attribut de requête `api_key` (CDC §51-52, §55).
 *
 * Le jeton en clair n'est jamais comparé directement : on hache en SHA-256 et
 * on interroge `token_hash` via le repository (le clair n'existe pas en base —
 * §52). Les clients publics (sans en-tête) traversent sans erreur : seules les
 * routes protégées exigeront une clé active (cf. EnsureApiKeyIsActive).
 */
class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $apiKeyRepository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            // Pas de clé fournie : la résolution reste vide. Les endpoints
            // publics fonctionnent ; les endpoints protégés rejetteront en aval.
            return $next($request);
        }

        $apiKey = $this->apiKeyRepository->findByToken($token);

        if ($apiKey !== null) {
            $request->attributes->set('api_key', $apiKey);
        }

        return $next($request);
    }
}
