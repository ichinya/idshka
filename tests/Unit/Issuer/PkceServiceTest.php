<?php

namespace Tests\Unit\Issuer;

use App\Domain\Issuer\Services\PkceService;
use Tests\TestCase;

class PkceServiceTest extends TestCase
{
    public function test_s256_code_challenge_matches_verifier(): void
    {
        $verifier = 'correct-horse-battery-staple-verifier-43-character-minimum';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $this->assertTrue(app(PkceService::class)->verifyS256($verifier, $challenge));
        $this->assertFalse(app(PkceService::class)->verifyS256('wrong-horse-battery-staple-verifier-43-character-minimum', $challenge));
    }

    public function test_plain_pkce_method_is_not_supported(): void
    {
        $this->assertFalse(app(PkceService::class)->verify('verifier', 'verifier', 'plain'));
    }
}
