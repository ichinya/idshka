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
            audience: 'apishka.ru',
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
            'aud' => 'apishka.ru',
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
}
