<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Gestionnaire d'exceptions — enveloppe d'erreur normalisée (CDC §50).
 *
 * Toute réponse JSON d'erreur HTTP porte la structure :
 *   { "error": { "code": "...", "message": "...", "request_id": "..." } }
 *
 * Les erreurs de validation (HTTP 422) suivent la même enveloppe, avec un
 * membre optionnel `errors` détaillant les champs fautifs (US-013).
 */
class Handler extends ExceptionHandler
{
    /**
     * Attributs ne devant jamais être flashés vers la session.
     *
     * @var list<string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    /**
     * Rendu structuré des erreurs HTTP pour les clients JSON.
     */
    public function render(mixed $request, Throwable $e): mixed
    {
        if ($request->expectsJson() && $e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'La requête contient des champs invalides.',
                    'request_id' => (string) Str::uuid(),
                    'errors' => $e->errors(),
                ],
            ], 422);
        }

        if ($request->expectsJson() && $e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();

            $response = response()->json([
                'error' => [
                    'code' => (string) $statusCode,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Erreur HTTP '.$statusCode,
                    'request_id' => (string) Str::uuid(),
                ],
            ], $statusCode);

            // US-022 (CDC §53) : propager les en-têtes de l'exception
            // (ex. Retry-After et X-RateLimit-* posés par ThrottleRequests
            // sur un 429), sinon l'enveloppe §50 les perdrait.
            foreach ($e->getHeaders() as $header => $value) {
                $response->headers->set($header, $value, true);
            }

            return $response;
        }

        return parent::render($request, $e);
    }
}
