<?php

namespace App\Domain\Issuer\Services;

use App\Support\SafeLogContext;
use Illuminate\Support\Facades\Log;

final class PkceService
{
    public const VERIFIER_REGEX = '/\A[A-Za-z0-9\-\._~]{43,128}\z/';

    public const S256_CHALLENGE_REGEX = '/\A[A-Za-z0-9\-_]{43}\z/';

    public function verify(string $verifier, string $challenge, string $method): bool
    {
        if ($method !== 'S256') {
            Log::warning('[issuer.pkce.verify] unsupported_method', SafeLogContext::from([
                'method' => $method,
            ]));

            return false;
        }

        return $this->verifyS256($verifier, $challenge);
    }

    public function verifyS256(string $verifier, string $challenge): bool
    {
        if (! $this->isValidVerifier($verifier) || ! $this->isValidS256Challenge($challenge)) {
            Log::warning('[issuer.pkce.verify] invalid_shape', SafeLogContext::from());

            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $matches = hash_equals($computed, $challenge);

        if (! $matches) {
            Log::warning('[issuer.pkce.verify] challenge_mismatch', SafeLogContext::from());
        }

        return $matches;
    }

    private function isValidVerifier(string $verifier): bool
    {
        return preg_match(self::VERIFIER_REGEX, $verifier) === 1;
    }

    private function isValidS256Challenge(string $challenge): bool
    {
        return preg_match(self::S256_CHALLENGE_REGEX, $challenge) === 1;
    }
}
