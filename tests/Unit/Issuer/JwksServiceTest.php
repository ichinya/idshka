<?php

namespace Tests\Unit\Issuer;

use App\Domain\Issuer\Services\JwksService;
use App\Domain\Issuer\Services\SigningKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwksServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_jwks_contains_active_and_next_public_keys_only(): void
    {
        $signingKeyService = $this->app->make(SigningKeyService::class);
        $active = $signingKeyService->createActiveKey();
        $next = $signingKeyService->prepareNextKey();

        $payload = $this->app->make(JwksService::class)->getPublicJwks();

        $this->assertArrayHasKey('keys', $payload);
        $this->assertCount(2, $payload['keys']);

        $kids = array_column($payload['keys'], 'kid');
        $this->assertContains($active->kid, $kids);
        $this->assertContains($next->kid, $kids);

        foreach ($payload['keys'] as $jwk) {
            $this->assertArrayHasKey('kty', $jwk);
            $this->assertArrayHasKey('kid', $jwk);
            $this->assertArrayHasKey('alg', $jwk);
            $this->assertArrayHasKey('use', $jwk);
            $this->assertArrayHasKey('n', $jwk);
            $this->assertArrayHasKey('e', $jwk);
            $this->assertArrayNotHasKey('private_key_encrypted', $jwk);
        }
    }
}
