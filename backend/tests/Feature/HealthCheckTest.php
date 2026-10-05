<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Contrat de l'endpoint de santé (CDC §76).
 *
 * Le healthcheck est le seul point d'entrée sans authentification : c'est
 * lui qui doit permettre à un orchestrator de savoir si le service est
 * réellement utilisable, extension PostGIS comprise.
 */
class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_is_publicly_accessible(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'api_version',
            'srid_default',
            'timestamp',
            'services' => ['database' => ['status', 'engine', 'postgis_version']],
        ]);
    }

    public function test_health_reports_healthy_when_postgis_is_reachable(): void
    {
        $this->assertPostGisAvailable();

        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('services.database.status', 'up');
    }

    public function test_default_srid_is_wgs84(): void
    {
        // Directive projet : EPSG:4326 en référentiel unique.
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('srid_default', 4326);
    }
}
