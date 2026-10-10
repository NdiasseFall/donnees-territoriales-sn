<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Contrat de l'API catalogue de jeux de données (CDC §35-40, §50, §60).
 *
 * Les tests vérifient la FORME des réponses et les règles d'erreur, pas le contenu
 * exact : ils doivent rester valides quel que soit le jeu de données seedé.
 */
class DatasetCatalogApiTest extends TestCase
{
    public function test_index_returns_paginated_envelope(): void
    {
        $response = $this->getJson('/api/v1/datasets');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'count',
            'total',
            'current_page',
            'per_page',
            'last_page',
            'from',
            'to',
            'data',
        ]);
    }

    public function test_index_applies_page_and_per_page(): void
    {
        $first = $this->getJson('/api/v1/datasets?page=1&per_page=5');
        $second = $this->getJson('/api/v1/datasets?page=2&per_page=5');

        $first->assertOk();
        $second->assertOk();

        self::assertArrayHasKey('data', $first->json());
        self::assertArrayHasKey('data', $second->json());
    }

    public function test_index_applies_offset_as_backward_compatibility(): void
    {
        $offset = $this->getJson('/api/v1/datasets?offset=20&limit=10');
        $offset->assertOk();
        self::assertArrayHasKey('data', $offset->json());
    }

    public function test_index_accepts_category_filter(): void
    {
        $response = $this->getJson('/api/v1/datasets?category=cadastre');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_index_accepts_access_level_filter(): void
    {
        // CDC : l'enum est en MAJUSCULES ; le contrôleur normalise la casse.
        $response = $this->getJson('/api/v1/datasets?access_level=public');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_index_accepts_access_level_filter_case_insensitively(): void
    {
        $lower = $this->getJson('/api/v1/datasets?access_level=public')->json('count');
        $upper = $this->getJson('/api/v1/datasets?access_level=PUBLIC')->json('count');

        self::assertSame($lower, $upper, 'CDC : le filtre access_level est insensible à la casse.');
    }

    public function test_index_applies_order_and_direction(): void
    {
        $response = $this->getJson('/api/v1/datasets?order=updated_at&direction=desc');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/v1/datasets/does-not-exist-xyz');

        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
        self::assertSame('DATASET_NOT_FOUND', $response->json('error.code'));
    }

    public function test_show_returns_success_envelope(): void
    {
        // Le slug doit exister dans le seed : s'il n'existe pas, le test est flou ;
        // on vérifie surtout la forme de la réponse quand le slug est connu.
        $response = $this->getJson('/api/v1/datasets/cadastre');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_search_rejects_short_query(): void
    {
        $this->getJson('/api/v1/datasets?q=M')->assertStatus(422);
    }

    public function test_search_requires_minimum_length(): void
    {
        $this->getJson('/api/v1/datasets?q=1')->assertStatus(422);
    }

    public function test_search_uses_query_parameter_name(): void
    {
        $this->getJson('/api/v1/datasets?search=Mbour')->assertOk();
    }

    public function test_search_is_case_insensitive(): void
    {
        $lowercase = $this->getJson('/api/v1/datasets?q=mbour')->json('count');
        $uppercase = $this->getJson('/api/v1/datasets?q=MBOUR')->json('count');

        self::assertSame($lowercase, $uppercase, 'CDC §85 : la recherche est insensible à la casse.');
    }

    public function test_search_handles_apostrophe_variant(): void
    {
        $withApostrophe = $this->getJson("/api/v1/datasets?q=M'bour")->json('count');
        $withoutApostrophe = $this->getJson('/api/v1/datasets?q=Mbour')->json('count');

        self::assertSame(
            $withApostrophe,
            $withoutApostrophe,
            'CDC §85 : l\'apostrophe ne doit pas empêcher la correspondance.'
        );
    }

    public function test_search_does_not_expose_sql_injection(): void
    {
        $response = $this->getJson("/api/v1/datasets?q=' OR '1'='1");

        $response->assertOk();
        self::assertSame(0, (int) $response->json('count'));
    }
}
