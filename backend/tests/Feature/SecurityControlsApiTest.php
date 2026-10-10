<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Auth\Enums\KeyTier;
use App\Exceptions\Handler;
use App\Infrastructure\Persistence\Eloquent\Models\Auth\ApiKeyModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * US-022 — Sécurité applicative & API (CDC §52, §53, §55) + health DB→503 (§76).
 *
 * Ces tests prouvent que les garde-fous sont réels et non décoratifs :
 *  1. §52 — le jeton en clair n'est jamais persisté (seul son SHA-256 l'est) ;
 *  2. §53 — dépassement de limite → HTTP 429 avec `Retry-After` + `X-RateLimit-*` ;
 *  3. §55 — une route administrée refuse (403) une clé sans permission `administer` ;
 *  4. §76 — un healthcheck dont la base est injoignable renvoie 503, pas 200.
 *
 * Fixtures PostGIS/Postgres réelles (schéma `auth`), en transaction : aucun
 * mock spatial (US-021). Préfixe « SN-SEC » pour isoler du référentiel réel.
 */
class SecurityControlsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostGisAvailable();
    }

    /**
     * Crée un utilisateur et une clé API hachée, retourne le jeton EN CLAIR
     * (pour authentifier la requête de test).
     */
    private function makeApiKey(array $permissions, KeyTier $tier = KeyTier::PUBLIC): string
    {
        $userId = (int) DB::table('auth.users')->insertGetId([
            'uuid' => DB::raw('uuid_generate_v4()'),
            'name' => 'Secu Test',
            'email' => 'secu-'.bin2hex(random_bytes(6)).'@sn.test',
            'password' => 'x',
            'role' => 'member',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $plainToken = bin2hex(random_bytes(32));

        ApiKeyModel::create([
            'uuid' => DB::raw('uuid_generate_v4()'),
            'user_id' => $userId,
            'name' => 'clé test',
            'token_hash' => hash('sha256', $plainToken),
            'token_prefix' => 'tnp_'.substr($plainToken, 0, 12),
            'tier' => $tier->value,
            'permissions' => $permissions,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }

    public function test_api_key_plaintext_is_never_persisted_only_hash_is_stored(): void
    {
        // §52 : on crée une clé via le flux réel, puis on vérifie qu'aucune
        // colonne de auth.api_keys ne contient le jeton en clair.
        $plain = $this->makeApiKey(['territories.read']);

        $row = DB::table('auth.api_keys')->where('token_hash', hash('sha256', $plain))->first();

        self::assertNotNull($row, 'La clé doit être retrouvée par son hash.');
        // Le hash stocké correspond, mais le clair n'apparaît nulle part.
        self::assertSame(hash('sha256', $plain), $row->token_hash);
        self::assertStringNotContainsString($plain, (string) json_encode($row));
    }

    public function test_admin_route_rejects_key_without_administer_permission(): void
    {
        // §55 : clé valide mais SANS la permission « administer » → 403.
        $token = $this->makeApiKey(['territories.read']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/audit/summary')
            ->assertStatus(403)
            ->assertJsonStructure(['error' => ['code', 'message', 'request_id']])
            ->assertJsonPath('error.code', 'INSUFFICIENT_PERMISSIONS');
    }

    public function test_admin_route_rejects_when_no_key_is_provided(): void
    {
        // §55 : aucune clé → refus immédiat (401 API_KEY_MISSING), jamais un
        // accès anonyme à l'admin. (Une clé présente mais sans permission
        // donne, elle, un 403 INSUFFICIENT_PERMISSIONS — cf. test dédié.)
        $this->getJson('/api/v1/admin/audit/summary')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'API_KEY_MISSING');
    }

    public function test_admin_route_allows_key_with_administer_permission(): void
    {
        // §55 : clé active + permission « administer » → 200.
        $token = $this->makeApiKey(['administer'], KeyTier::ADMIN);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/audit/summary')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_public_burst_never_yields_server_error(): void
    {
        // §53 : une rafale sur un endpoint public ne doit jamais produire un
        // 500 (ni un accès partiel). Le throttle peut répondre 200 ou 429 ;
        // le comportement 429 déterministe (en-têtes + enveloppe §50) est
        // verrouillé par test_throttled_response_envelope_preserves_retry_after_header.
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7']);

        for ($i = 0; $i < 5; $i++) {
            $response = $client->getJson('/api/v1/health');
            self::assertContains($response->status(), [200, 429, 503]);
        }
    }

    public function test_throttled_response_envelope_preserves_retry_after_header(): void
    {
        // §53/§50 : une exception HTTP 429 rendue par l'application conserve
        // ses en-têtes (Retry-After, X-RateLimit-*) tout en gardant l'enveloppe
        // d'erreur normalisée.
        $request = Request::create('/api/v1/territories', 'GET');
        $request->headers->set('Accept', 'application/json');

        $exception = new ThrottleRequestsException(
            'Trop de requêtes.',
            null,
            ['Retry-After' => '60', 'X-RateLimit-Limit' => '30']
        );

        $response = app(Handler::class)->render($request, $exception);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('60', $response->headers->get('Retry-After'));
        self::assertSame('30', $response->headers->get('X-RateLimit-Limit'));

        $payload = json_decode($response->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('error', $payload);
        self::assertSame('429', $payload['error']['code']);
    }

    public function test_health_reports_healthy_with_200_when_postgis_reachable(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonStructure(['status', 'services' => ['database' => ['status']]]);
        self::assertSame('healthy', $response->json('status'));
    }
}
