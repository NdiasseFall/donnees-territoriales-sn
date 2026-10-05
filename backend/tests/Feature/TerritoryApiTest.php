<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Contrat de l'API territoires (CDC §35-39, §50).
 *
 * Ces tests vérifient la FORME des réponses et les règles d'erreur, pas le
 * contenu exact : ils doivent rester valides quel que soit le jeu de données
 * ANAT chargé, puisque les codes canoniques relèvent de la décision D-3.
 */
class TerritoryApiTest extends TestCase
{
    public function test_index_returns_paginated_envelope(): void
    {
        $response = $this->getJson('/api/v1/territories');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_index_accepts_level_filter(): void
    {
        $this->getJson('/api/v1/territories?level=1')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_geojson_format_is_served_with_correct_content_type(): void
    {
        $response = $this->get('/api/v1/territories?format=geojson&level=1');

        $response->assertOk();

        // Directive projet : GeoJSON = application/geo+json (CDC §34).
        self::assertStringContainsString(
            'application/geo+json',
            (string) $response->headers->get('Content-Type'),
            'Le format GeoJSON doit être servi en application/geo+json.'
        );
    }

    public function test_geojson_response_conforms_to_rfc7946(): void
    {
        $payload = $this->getJson('/api/v1/territories?format=geojson&limit=5')->json();

        self::assertIsArray($payload);
        self::assertSame('FeatureCollection', $payload['type'] ?? null, 'RFC 7946 : type = FeatureCollection.');
        self::assertArrayHasKey('features', $payload);
        self::assertIsArray($payload['features']);

        foreach ($payload['features'] as $feature) {
            self::assertSame('Feature', $feature['type'] ?? null, 'RFC 7946 : chaque feature a type = Feature.');
            self::assertArrayHasKey('geometry', $feature);
            self::assertArrayHasKey('properties', $feature);

            $geometry = $feature['geometry'];

            // RFC 7946 §3.1.1 : le.type est obligatoire pour les géométries.
            self::assertNotEmpty(
                $geometry['type'] ?? null,
                'RFC 7946 : la clé "type" est obligatoire dans une géométrie.'
            );

            self::assertNotNull(
                $geometry['coordinates'] ?? null,
                'RFC 7946 : la clé "coordinates" est obligatoire dans une géométrie.'
            );

            // Le CRS explicite est interdit en GeoJSON 2008 / RFC 7946 (tout est en WGS84).
            self::assertArrayNotHasKey(
                'crs',
                $geometry,
                'RFC 7946 interdit la clé "crs" : toutes les coordonnées sont en EPSG:4326.'
            );
        }
    }

    public function test_unknown_code_returns_structured_error(): void
    {
        $response = $this->getJson('/api/v1/territories/CODE-INEXISTANT-999999');

        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
    }
}
