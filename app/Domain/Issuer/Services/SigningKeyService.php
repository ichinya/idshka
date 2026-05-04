<?php

namespace App\Domain\Issuer\Services;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Models\SigningKey;
use App\Support\SafeLogContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenSSLAsymmetricKey;
use Throwable;

final class SigningKeyService
{
    public function requireActiveKey(): SigningKey
    {
        Log::debug('[issuer.signing_key.require_active] started', SafeLogContext::from());

        /** @var SigningKey|null $key */
        $key = SigningKey::query()
            ->where('status', SigningKeyStatus::Active->value)
            ->where(function ($query): void {
                $query->whereNull('activated_at')
                    ->orWhere('activated_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('retired_at')
                    ->orWhere('retired_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();

        if ($key === null) {
            Log::warning('[issuer.signing_key.require_active] missing_active_key', SafeLogContext::from());

            throw SigningKeyStateException::missingActiveKey();
        }

        Log::debug('[issuer.signing_key.require_active] completed', SafeLogContext::from([
            'key_id' => $key->id,
            'kid' => $key->kid,
        ]));

        return $key;
    }

    public function createActiveKey(): SigningKey
    {
        return $this->createKey(SigningKeyStatus::Active, CarbonImmutable::now());
    }

    public function prepareNextKey(): SigningKey
    {
        Log::debug('[issuer.signing_key.prepare_next] started', SafeLogContext::from());

        /** @var SigningKey|null $existing */
        $existing = SigningKey::query()
            ->where('status', SigningKeyStatus::Next->value)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            Log::info('[issuer.signing_key.prepare_next] already_exists', SafeLogContext::from([
                'key_id' => $existing->id,
                'kid' => $existing->kid,
            ]));

            return $existing;
        }

        return $this->createKey(SigningKeyStatus::Next, null);
    }

    public function activateNextKey(): SigningKey
    {
        Log::info('[issuer.signing_key.activate_next] started', SafeLogContext::from());

        /** @var SigningKey $activated */
        $activated = DB::transaction(function (): SigningKey {
            /** @var SigningKey|null $next */
            $next = SigningKey::query()
                ->where('status', SigningKeyStatus::Next->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($next === null) {
                Log::warning('[issuer.signing_key.activate_next] missing_next_key', SafeLogContext::from());

                throw SigningKeyStateException::invalidState();
            }

            $now = CarbonImmutable::now();
            $next->forceFill([
                'status' => SigningKeyStatus::Active->value,
                'activated_at' => $now->toDateTimeString(),
                'retired_at' => null,
            ])->save();

            return $next->refresh();
        });

        Cache::forget('issuer:jwks:public');

        Log::info('[issuer.signing_key.activate_next] completed', SafeLogContext::from([
            'key_id' => $activated->id,
            'kid' => $activated->kid,
            'old_status' => SigningKeyStatus::Next->value,
            'new_status' => SigningKeyStatus::Active->value,
            'activated_at' => $activated->activated_at?->toISOString(),
        ]));

        return $activated;
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     retirable_count: int,
     *     retired_count: int,
     *     blocked_count: int,
     *     blocked_keys: list<array{key_id: int, kid: string, blocker_count: int}>,
     *     retired_kids: list<string>
     * }
     */
    public function retireExpiredKeys(bool $dryRun = false): array
    {
        Log::info('[issuer.signing_key.retire_expired] started', SafeLogContext::from([
            'dry_run' => $dryRun,
        ]));

        $now = CarbonImmutable::now();
        $newestActive = $this->newestActiveKey();

        if ($newestActive === null) {
            Log::warning('[issuer.signing_key.retire_expired] missing_active_key', SafeLogContext::from([
                'dry_run' => $dryRun,
            ]));

            return [
                'dry_run' => $dryRun,
                'retirable_count' => 0,
                'retired_count' => 0,
                'blocked_count' => 0,
                'blocked_keys' => [],
                'retired_kids' => [],
            ];
        }

        $transientJwtCutoff = $now->subSeconds($this->maxTransientJwtTtlSeconds());

        if (
            $newestActive->activated_at !== null
            && $newestActive->activated_at->gt($transientJwtCutoff)
        ) {
            Log::info('[issuer.signing_key.retire_expired] newest_active_still_within_ttl', SafeLogContext::from([
                'key_id' => $newestActive->id,
                'kid' => $newestActive->kid,
                'activated_at' => $newestActive->activated_at->toISOString(),
            ]));

            return [
                'dry_run' => $dryRun,
                'retirable_count' => 0,
                'retired_count' => 0,
                'blocked_count' => 0,
                'blocked_keys' => [],
                'retired_kids' => [],
            ];
        }

        $candidates = SigningKey::query()
            ->where('status', SigningKeyStatus::Active->value)
            ->where('id', '!=', $newestActive->id)
            ->where(function ($query): void {
                $query->whereNull('retired_at')
                    ->orWhere('retired_at', '>', now());
            })
            ->orderBy('id')
            ->get();

        $retirableCount = 0;
        $retiredCount = 0;
        $blockedCount = 0;
        $blockedKeys = [];
        $retiredKids = [];

        foreach ($candidates as $candidate) {
            $blockerCount = $this->activeApiTokenBlockerCount($candidate, $now);

            if ($blockerCount > 0) {
                $blockedCount += $blockerCount;
                $blockedKeys[] = [
                    'key_id' => $candidate->id,
                    'kid' => $candidate->kid,
                    'blocker_count' => $blockerCount,
                ];

                Log::warning('[issuer.signing_key.retire_expired] blocked_by_api_tokens', SafeLogContext::from([
                    'key_id' => $candidate->id,
                    'kid' => $candidate->kid,
                    'blocking_api_tokens_count' => $blockerCount,
                ]));

                continue;
            }

            $retirableCount++;

            if ($dryRun) {
                continue;
            }

            $candidate->forceFill([
                'status' => SigningKeyStatus::Retired->value,
                'retired_at' => $now->toDateTimeString(),
            ])->save();

            $retiredCount++;
            $retiredKids[] = $candidate->kid;

            Log::info('[issuer.signing_key.retire_expired] retired_key', SafeLogContext::from([
                'key_id' => $candidate->id,
                'kid' => $candidate->kid,
                'old_status' => SigningKeyStatus::Active->value,
                'new_status' => SigningKeyStatus::Retired->value,
                'retired_at' => $now->toISOString(),
            ]));
        }

        if ($retiredCount > 0) {
            Cache::forget('issuer:jwks:public');
        }

        Log::info('[issuer.signing_key.retire_expired] completed', SafeLogContext::from([
            'dry_run' => $dryRun,
            'retirable_count' => $retirableCount,
            'retired_count' => $retiredCount,
            'blocked_count' => $blockedCount,
        ]));

        return [
            'dry_run' => $dryRun,
            'retirable_count' => $retirableCount,
            'retired_count' => $retiredCount,
            'blocked_count' => $blockedCount,
            'blocked_keys' => $blockedKeys,
            'retired_kids' => $retiredKids,
        ];
    }

    public function forceRetireByKid(string $kid): SigningKey
    {
        Log::warning('[issuer.signing_key.force_retire] started', SafeLogContext::from([
            'kid' => $kid,
        ]));

        /** @var SigningKey|null $key */
        $key = SigningKey::query()
            ->where('kid', $kid)
            ->first();

        if ($key === null) {
            Log::warning('[issuer.signing_key.force_retire] missing_key', SafeLogContext::from([
                'kid' => $kid,
            ]));

            throw SigningKeyStateException::invalidState();
        }

        $oldStatus = $key->status;
        $now = CarbonImmutable::now();
        $key->forceFill([
            'status' => SigningKeyStatus::Retired->value,
            'retired_at' => $key->retired_at?->toDateTimeString() ?? $now->toDateTimeString(),
        ])->save();

        Cache::forget('issuer:jwks:public');

        Log::warning('[issuer.signing_key.force_retire] completed', SafeLogContext::from([
            'key_id' => $key->id,
            'kid' => $key->kid,
            'old_status' => $oldStatus,
            'new_status' => SigningKeyStatus::Retired->value,
            'retired_at' => $key->retired_at?->toISOString(),
        ]));

        return $key->refresh();
    }

    public function rollbackToPreviousActive(): SigningKey
    {
        Log::warning('[issuer.signing_key.rollback] started', SafeLogContext::from());

        $activeKeys = SigningKey::query()
            ->where('status', SigningKeyStatus::Active->value)
            ->where(function ($query): void {
                $query->whereNull('retired_at')
                    ->orWhere('retired_at', '>', now());
            })
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        $newest = $activeKeys->get(0);
        $previous = $activeKeys->get(1);

        if (! ($newest instanceof SigningKey) || ! ($previous instanceof SigningKey)) {
            Log::warning('[issuer.signing_key.rollback] missing_previous_active_key', SafeLogContext::from());

            throw SigningKeyStateException::invalidState();
        }

        $now = CarbonImmutable::now();
        $newest->forceFill([
            'status' => SigningKeyStatus::Retired->value,
            'retired_at' => $now->toDateTimeString(),
        ])->save();

        Cache::forget('issuer:jwks:public');

        Log::warning('[issuer.signing_key.rollback] completed', SafeLogContext::from([
            'retired_key_id' => $newest->id,
            'retired_kid' => $newest->kid,
            'active_key_id' => $previous->id,
            'kid' => $previous->kid,
            'retired_at' => $now->toISOString(),
        ]));

        return $previous->refresh();
    }

    /**
     * @return list<array{
     *     key_id: int,
     *     kid: string,
     *     status: string,
     *     algorithm: string,
     *     activated_at: string|null,
     *     retired_at: string|null,
     *     is_signing: bool,
     *     jwks_published: bool,
     *     blocking_api_tokens_count: int
     * }>
     */
    public function statusReport(): array
    {
        Log::debug('[issuer.signing_key.status] started', SafeLogContext::from());

        $now = CarbonImmutable::now();
        $newestActive = $this->newestActiveKey();
        $rows = SigningKey::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (SigningKey $key) use ($newestActive, $now): array {
                return [
                    'key_id' => $key->id,
                    'kid' => $key->kid,
                    'status' => $key->status,
                    'algorithm' => $key->algorithm,
                    'activated_at' => $key->activated_at?->toISOString(),
                    'retired_at' => $key->retired_at?->toISOString(),
                    'is_signing' => $newestActive !== null && $key->id === $newestActive->id,
                    'jwks_published' => $this->isPublishedInJwks($key, $now),
                    'blocking_api_tokens_count' => $this->activeApiTokenBlockerCount($key, $now),
                ];
            })
            ->values()
            ->all();

        Log::debug('[issuer.signing_key.status] completed', SafeLogContext::from([
            'keys_count' => count($rows),
        ]));

        return $rows;
    }

    public function decryptPrivateKey(SigningKey $signingKey): string
    {
        try {
            return Crypt::decryptString($signingKey->private_key_encrypted);
        } catch (Throwable $exception) {
            Log::error('[issuer.signing_key.decrypt] failed', SafeLogContext::from([
                'key_id' => $signingKey->id,
                'kid' => $signingKey->kid,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            throw SigningKeyStateException::privateKeyDecryptFailed();
        }
    }

    private function createKey(SigningKeyStatus $status, ?CarbonImmutable $activatedAt): SigningKey
    {
        $algorithm = $this->resolveAlgorithm();

        if ($algorithm !== 'RS256') {
            throw SigningKeyStateException::unsupportedAlgorithm($algorithm);
        }

        Log::info('[issuer.signing_key.create] started', SafeLogContext::from([
            'status' => $status->value,
            'algorithm' => $algorithm,
        ]));

        [$privatePem, $publicPem, $publicJwk] = $this->generateRsaKeyPair();

        /** @var SigningKey $key */
        $key = SigningKey::query()->create([
            'kid' => (string) Str::ulid(),
            'algorithm' => $algorithm,
            'public_jwk' => $publicJwk,
            'public_key_pem' => $publicPem,
            'private_key_encrypted' => Crypt::encryptString($privatePem),
            'status' => $status->value,
            'activated_at' => $activatedAt?->toDateTimeString(),
            'retired_at' => null,
        ]);

        Cache::forget('issuer:jwks:public');

        Log::info('[issuer.signing_key.create] completed', SafeLogContext::from([
            'key_id' => $key->id,
            'kid' => $key->kid,
            'status' => $status->value,
        ]));

        return $key;
    }

    private function resolveAlgorithm(): string
    {
        $algorithms = config('issuer.allowed_algs', ['RS256']);
        $algorithm = (string) ($algorithms[0] ?? 'RS256');

        return trim($algorithm) !== '' ? trim($algorithm) : 'RS256';
    }

    private function newestActiveKey(): ?SigningKey
    {
        /** @var SigningKey|null $key */
        $key = SigningKey::query()
            ->where('status', SigningKeyStatus::Active->value)
            ->where(function ($query): void {
                $query->whereNull('activated_at')
                    ->orWhere('activated_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('retired_at')
                    ->orWhere('retired_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();

        return $key;
    }

    private function maxTransientJwtTtlSeconds(): int
    {
        return max(
            1,
            (int) config('issuer.user_api_token_ttl_seconds', 900),
            (int) config('issuer.web_access_token_ttl_seconds', 600),
            (int) config('issuer.id_token_ttl_seconds', 300),
        );
    }

    private function activeApiTokenBlockerCount(SigningKey $key, CarbonImmutable $now): int
    {
        return ApiToken::query()
            ->where('signing_key_id', $key->id)
            ->whereNull('revoked_at')
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->count();
    }

    private function isPublishedInJwks(SigningKey $key, CarbonImmutable $now): bool
    {
        if (! in_array($key->status, [
            SigningKeyStatus::Active->value,
            SigningKeyStatus::Next->value,
        ], true)) {
            return false;
        }

        return $key->retired_at === null || $key->retired_at->gt($now);
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, string>}
     */
    private function generateRsaKeyPair(): array
    {
        $bits = (int) config('issuer.keys.rsa_bits', 2048);
        $openSslConfig = base_path('config/openssl.cnf');
        $options = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => $bits,
        ];

        if (is_file($openSslConfig)) {
            $options['config'] = $openSslConfig;
        }

        $resource = openssl_pkey_new($options);

        if (! ($resource instanceof OpenSSLAsymmetricKey)) {
            Log::error('[issuer.signing_key.generate] openssl_pkey_new_failed', SafeLogContext::from([
                'bits' => $bits,
                'openssl_errors' => $this->collectOpenSslErrors(),
            ]));

            throw SigningKeyStateException::generationFailed();
        }

        $privatePem = '';
        $exportOptions = isset($options['config']) ? ['config' => $options['config']] : [];
        $exported = openssl_pkey_export($resource, $privatePem, null, $exportOptions);
        $details = openssl_pkey_get_details($resource);

        if (
            $exported !== true
            || ! is_array($details)
            || ! isset($details['key'])
            || ! is_string($details['key'])
            || ! isset($details['rsa'])
            || ! is_array($details['rsa'])
        ) {
            Log::error('[issuer.signing_key.generate] openssl_key_export_failed', SafeLogContext::from([
                'bits' => $bits,
                'exported' => $exported,
                'openssl_errors' => $this->collectOpenSslErrors(),
            ]));

            throw SigningKeyStateException::generationFailed();
        }

        $modulus = $details['rsa']['n'] ?? null;
        $exponent = $details['rsa']['e'] ?? null;

        if (! is_string($modulus) || ! is_string($exponent)) {
            throw SigningKeyStateException::invalidState();
        }

        return [
            $privatePem,
            $details['key'],
            [
                'kty' => 'RSA',
                'n' => $this->base64UrlEncode($modulus),
                'e' => $this->base64UrlEncode($exponent),
            ],
        ];
    }

    private function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    /**
     * @return list<string>
     */
    private function collectOpenSslErrors(): array
    {
        $errors = [];

        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return $errors;
    }
}
