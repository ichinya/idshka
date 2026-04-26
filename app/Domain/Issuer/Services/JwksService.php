<?php

namespace App\Domain\Issuer\Services;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Models\SigningKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class JwksService
{
    public function __construct(
        private readonly SigningKeyService $signingKeyService,
    ) {}

    /**
     * @return array{keys: list<array<string, string>>}
     */
    public function getPublicJwks(): array
    {
        Log::debug('[issuer.jwks.get] started');

        // Fail closed: JWKS must not publish when the active key state is broken.
        $this->signingKeyService->requireActiveKey();

        $cacheTtlSeconds = max(1, (int) config('issuer.jwks.cache_seconds', 120));
        $cacheKey = 'issuer:jwks:public';

        /** @var array{keys: list<array<string, string>>} $payload */
        $payload = Cache::remember($cacheKey, $cacheTtlSeconds, function (): array {
            $keys = SigningKey::query()
                ->whereIn('status', [
                    SigningKeyStatus::Active->value,
                    SigningKeyStatus::Next->value,
                ])
                ->where(function ($query): void {
                    $query->whereNull('retired_at')
                        ->orWhere('retired_at', '>', now());
                })
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [SigningKeyStatus::Active->value])
                ->orderBy('id')
                ->get();

            return [
                'keys' => $keys
                    ->map(fn (SigningKey $key): array => $this->toPublicJwk($key))
                    ->values()
                    ->all(),
            ];
        });

        Log::debug('[issuer.jwks.get] completed', [
            'keys_count' => count($payload['keys']),
        ]);

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function toPublicJwk(SigningKey $signingKey): array
    {
        $publicJwk = $signingKey->public_jwk;
        $modulus = $publicJwk['n'] ?? null;
        $exponent = $publicJwk['e'] ?? null;
        $kty = $publicJwk['kty'] ?? null;

        if (! is_string($modulus) || ! is_string($exponent) || ! is_string($kty)) {
            Log::error('[issuer.jwks.get] invalid_key_state', [
                'key_id' => $signingKey->id,
                'kid' => $signingKey->kid,
            ]);

            throw SigningKeyStateException::invalidState();
        }

        return [
            'kty' => $kty,
            'kid' => $signingKey->kid,
            'alg' => $signingKey->algorithm,
            'use' => 'sig',
            'n' => $modulus,
            'e' => $exponent,
        ];
    }
}
