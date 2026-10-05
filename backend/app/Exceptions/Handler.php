<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Gestionnaire d'exceptions — enveloppe d'erreur normalisée (CDC §50).
 *
 * Toute réponse JSON d'erreur HTTP porte la structure :
 *   { "error": { "code": "...", "message": "...", "request_id": "..." } }
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
        if ($request->expectsJson() && $e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();

            return response()->json([
                'error' => [
                    'code' => (string) $statusCode,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Erreur HTTP '.$statusCode,
                    'request_id' => (string) Str::uuid(),
                ],
            ], $statusCode);
        }

        return parent::render($request, $e);
    }
}
