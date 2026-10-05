<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Contrat du moteur de recherche (CDC §42, §85).
 *
 * Le CDC exige que « Mbour », « mbour », « MBOUR » et « M'bour » donnent le
 * même résultat. Ce test verrouille cette exigence de bout en bout.
 */
class SearchApiTest extends TestCase
{
    public function test_search_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/search?q=Mbour');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'query', 'count', 'data']);
    }

    public function test_search_is_case_insensitive(): void
    {
        $lowercase = $this->getJson('/api/v1/search?q=mbour')->json('count');
        $uppercase = $this->getJson('/api/v1/search?q=MBOUR')->json('count');

        self::assertSame(
            $lowercase,
            $uppercase,
            'CDC §85 : la recherche doit être insensible à la casse.'
        );
    }

    public function test_search_returns_same_results_for_apostrophe_variant(): void
    {
        $withApostrophe = $this->getJson("/api/v1/search?q=M'bour")->json('count');
        $withoutApostrophe = $this->getJson('/api/v1/search?q=Mbour')->json('count');

        self::assertSame(
            $withApostrophe,
            $withoutApostrophe,
            "CDC §85 : l'apostrophe ne doit pas empêcher la correspondance."
        );
    }

    public function test_search_requires_minimum_length(): void
    {
        // En dessous de 2 caractères la recherche doit être refusée, pas coûteuse.
        $this->getJson('/api/v1/search?q=M')->assertStatus(422);
    }

    public function test_search_requires_query_parameter(): void
    {
        $this->getJson('/api/v1/search')->assertStatus(422);
    }

    public function test_search_rejects_sql_injection_attempts(): void
    {
        // US-022 : la saisie ne doit jamais être concaténée dans du SQL.
        $response = $this->getJson("/api/v1/search?q=' OR '1'='1");

        $response->assertOk();

        self::assertSame(
            0,
            (int) $response->json('count'),
            "Une tentative d'injection ne doit pas retourner de résultats."
        );
    }
}
