<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * US-013 — Contrat OpenAPI 3.1 (CDC §61, §100.9).
 *
 * Le fichier `storage/api/openapi.yaml` est la source de vérité du contrat
 * API v1. Ce test verrouille la cohérence bidirectionnelle entre :
 *   - les routes réellement enregistrées dans Laravel (groupe `/api`), et
 *   - les chemins + méthodes documentés dans le contrat OpenAPI.
 *
 * Objectif : aucune dérive possible entre l'implémentation et la documentation
 * servie par `/api/docs` (Redoc) sans faire échouer la CI.
 *
 * Ce test n'a pas besoin de PostGIS : il inspecte les routes et parse le YAML.
 */
class OpenApiDocumentationTest extends TestCase
{
    /**
     * Charge et parse le contrat OpenAPI servi par l'API.
     *
     * @return array<string, mixed>
     */
    private function loadSpec(): array
    {
        $path = storage_path('api/openapi.yaml');

        self::assertFileExists($path, 'Le contrat OpenAPI storage/api/openapi.yaml est absent.');

        return Yaml::parseFile($path);
    }

    /**
     * Ensemble des opérations réelles exposées par le groupe `/api`.
     *
     * @return array<string, true> Clés au format "METHOD /chemin" (ex. "GET /api/v1/territories").
     */
    private function laravelApiRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $path = '/'.rtrim($uri, '/');
            if ($path === '') {
                $path = '/';
            }

            foreach ($route->methods() as $method) {
                // HEAD est implicite pour toute route GET : on ne le documente pas.
                if ($method === 'HEAD') {
                    continue;
                }

                $routes[strtoupper($method).' '.$path] = true;
            }
        }

        return $routes;
    }

    /**
     * Ensemble des opérations documentées dans le contrat OpenAPI.
     *
     * @param  array<string, mixed>  $spec
     * @return array<string, true> Clés au format "METHOD /chemin".
     */
    private function openApiOperations(array $spec): array
    {
        $operations = [];
        $httpMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];
        $paths = $spec['paths'] ?? [];

        foreach ($paths as $path => $methods) {
            foreach (array_keys((array) $methods) as $method) {
                $method = strtolower((string) $method);

                if (in_array($method, $httpMethods, true)) {
                    $operations[strtoupper($method).' '.(string) $path] = true;
                }
            }
        }

        return $operations;
    }

    public function test_openapi_spec_is_valid_and_well_formed(): void
    {
        $spec = $this->loadSpec();

        self::assertIsArray($spec, 'Le contrat OpenAPI doit être un document YAML valide.');
        self::assertSame('3.1.0', $spec['openapi'] ?? null, 'Le contrat doit déclarer OpenAPI 3.1.0.');
        self::assertArrayHasKey('info', $spec, 'Le contrat doit contenir une section info.');
        self::assertArrayHasKey('paths', $spec, 'Le contrat doit contenir une section paths.');
        self::assertNotEmpty($spec['paths'], 'Le contrat doit documenter au moins un chemin.');
    }

    public function test_every_laravel_api_route_is_documented_in_openapi(): void
    {
        $documented = $this->openApiOperations($this->loadSpec());

        foreach (array_keys($this->laravelApiRoutes()) as $operation) {
            self::assertArrayHasKey(
                $operation,
                $documented,
                "Route Laravel « {$operation} » absente du contrat OpenAPI. "
                .'Documentez-la dans storage/api/openapi.yaml.'
            );
        }
    }

    public function test_every_openapi_path_maps_to_a_real_laravel_route(): void
    {
        $routes = $this->laravelApiRoutes();

        foreach (array_keys($this->openApiOperations($this->loadSpec())) as $operation) {
            self::assertArrayHasKey(
                $operation,
                $routes,
                "Chemin OpenAPI « {$operation} » sans route Laravel correspondante. "
                .'Retirez-le du contrat ou implémentez la route.'
            );
        }
    }

    public function test_api_docs_page_is_served_as_html(): void
    {
        $response = $this->get('/api/docs');

        $response->assertOk();
        $response->assertSee('redoc', false);
    }

    public function test_api_docs_spec_is_served_as_yaml(): void
    {
        $response = $this->get('/api/docs/openapi.yaml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/yaml; charset=utf-8');
        self::assertStringContainsString('openapi:', $response->getContent());
    }
}
