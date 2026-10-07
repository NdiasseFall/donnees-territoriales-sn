<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Enveloppe d'erreur normalisée (CDC §50).
 *
 * Toute réponse d'erreur de l'API porte la structure :
 *   { "error": { "code": "...", "message": "...", "request_id": "..." } }
 */
final class ApiError
{
    public static function make(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => (string) Str::uuid(),
            ],
        ], $status);
    }
}
