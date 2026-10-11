<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Documentation OpenAPI 3.1 de l'API v1 (US-013, CDC §61).
 *
 * - `show` : page interactive publique sur `/api/docs` (Redoc).
 * - `spec` : contrat OpenAPI brut, servi depuis `storage/api/openapi.yaml`
 *   (source de vérité unique, validée en CI par `@redocly/cli`).
 */
class ApiDocsController extends Controller
{
    /**
     * Chemin du contrat OpenAPI versionné dans le dépôt.
     */
    private const SPEC_PATH = 'api/openapi.yaml';

    /**
     * Page HTML de documentation interactive (lecture anonyme).
     */
    public function show(): View
    {
        return view('api-docs', [
            'specUrl' => route('api.docs.spec'),
        ]);
    }

    /**
     * Spécification OpenAPI 3.1 au format YAML.
     */
    public function spec(): Response
    {
        $path = storage_path(self::SPEC_PATH);

        abort_unless(is_file($path), 500, 'Spécification OpenAPI absente.');

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/yaml; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
