<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-022 — Sécurité de l'API (CDC §52, §53, §55).
 *
 * Ces tests vérifient que les garde-fous sont réels et non décoratifs.
 */
class SecurityApiTest extends TestCase
{
    public function test_api_keys_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/keys')->assertStatus(401);
    }

    public function test_creating_an_api_key_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/keys', ['name' => 'test'])->assertStatus(401);
    }

    public function test_revoking_an_api_key_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/auth/keys/some-uuid')->assertStatus(401);
    }

    public function test_public_endpoints_do_not_require_authentication(): void
    {
        // Les données publiées doivent rester accessibles sans clé (§51).
        $this->getJson('/api/v1/territories')->assertOk();
    }

    public function test_unknown_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/endpoint-inexistant')
            ->assertStatus(404)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }
}
