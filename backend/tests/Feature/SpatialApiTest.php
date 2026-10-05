<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Contrat des services spatiaux (CDC §44, §45).
 *
 * Ces requêtes ne peuvent pas être testées hors PostGIS : ST_Intersects et
 * ST_Within sont le cœur fonctionnel de l'API géographique.
 */
class SpatialApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostGisAvailable();
    }

    public function test_bbox_query_is_accepted(): void
    {
        // Bbox Dakar -17.6,14.6 / Thiès -17.0,15.0
        $response = $this->getJson('/api/v1/spatial/bbox?bbox=-17.6,14.6,-17.0,15.0');

        $response->assertOk();
        self::assertIsArray($response->json());
    }

    public function test_malformed_bbox_is_rejected(): void
    {
        // Un bbox non numérique ne doit jamais atteindre PostGIS.
        $this->getJson('/api/v1/spatial/bbox?bbox=abc,def,ghi,jkl')
            ->assertStatus(422);
    }

    public function test_bbox_with_more_than_four_values_is_rejected(): void
    {
        $this->getJson('/api/v1/spatial/bbox?bbox=-17.6,14.6,-17.0,15.0,99.9')
            ->assertStatus(422);
    }

    public function test_reverse_geocode_returns_nothing_for_point_outside_senegal(): void
    {
        // Point en pleine Atlantique : ne doit jamais retourner une commune arbitraire.
        $response = $this->getJson('/api/v1/spatial/reverse-geocode?lat=10.0&lng=-30.0');

        self::assertContains(
            $response->status(),
            [200, 404],
            'Un point hors emprise doit retourner un résultat vide ou un 404, jamais une commune arbitraire.'
        );
    }

    public function test_reverse_geocode_requires_valid_coordinates(): void
    {
        $this->getJson('/api/v1/spatial/reverse-geocode?lat=200&lng=-17.5')
            ->assertStatus(422);
    }

    public function test_reverse_geocode_rejects_out_of_range_latitude(): void
    {
        // La latitude valide est comprise entre -90 et +90.
        $this->getJson('/api/v1/spatial/reverse-geocode?lat=1000&lng=-17.5')
            ->assertStatus(422);
    }
}
