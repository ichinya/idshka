<?php

namespace Tests\Unit\Auth;

use App\Contracts\Auth\JwtClaims;
use PHPUnit\Framework\TestCase;

class JwtClaimsTest extends TestCase
{
    public function test_claims_payload_contains_required_fields(): void
    {
        $claims = new JwtClaims(
            issuer: 'https://idshka.ru',
            audience: 'example.test',
            subject: '42',
            siteId: 'site_01kq000000000000000000000000000001',
            tokenType: JwtClaims::TOKEN_TYPE_USER_API,
            scopes: ['orders.read', 'orders.write'],
            permissions: ['orders.read'],
            jti: 'jti-123',
            issuedAt: 1_700_000_000,
            notBefore: 1_700_000_000,
            expiresAt: 1_700_000_900,
        );

        $this->assertSame([
            'iss' => 'https://idshka.ru',
            'aud' => 'example.test',
            'sub' => '42',
            'site_id' => 'site_01kq000000000000000000000000000001',
            'token_type' => 'user_api',
            'scope' => 'orders.read orders.write',
            'permissions' => ['orders.read'],
            'jti' => 'jti-123',
            'iat' => 1_700_000_000,
            'nbf' => 1_700_000_000,
            'exp' => 1_700_000_900,
        ], $claims->toArray());
    }

    public function test_id_token_claims_include_nonce_and_oidc_token_type(): void
    {
        $claims = new JwtClaims(
            issuer: 'https://idshka.ru',
            audience: 'client_01kq000000000000000000000000000001',
            subject: '42',
            siteId: 'site_01kq000000000000000000000000000001',
            tokenType: JwtClaims::TOKEN_TYPE_ID_TOKEN,
            scopes: ['openid', 'profile', 'email'],
            permissions: [],
            jti: 'jti-id-token-123',
            issuedAt: 1_700_000_000,
            notBefore: 1_700_000_000,
            expiresAt: 1_700_000_300,
            nonce: 'nonce-123',
        );

        $payload = $claims->toArray();

        $this->assertSame('id_token', $payload['token_type']);
        $this->assertSame('nonce-123', $payload['nonce']);
        $this->assertSame('openid profile email', $payload['scope']);
        $this->assertArrayNotHasKey('permissions', $payload);
    }

    public function test_claims_payload_can_omit_expiration_for_non_expiring_user_api_token(): void
    {
        $claims = new JwtClaims(
            issuer: 'https://idshka.ru',
            audience: 'example.test',
            subject: '42',
            siteId: 'site_01kq000000000000000000000000000001',
            tokenType: JwtClaims::TOKEN_TYPE_USER_API,
            scopes: ['orders.read'],
            permissions: ['orders.read'],
            jti: 'jti-123',
            issuedAt: 1_700_000_000,
            notBefore: 1_700_000_000,
            expiresAt: null,
        );

        $this->assertArrayNotHasKey('exp', $claims->toArray());
    }
}
