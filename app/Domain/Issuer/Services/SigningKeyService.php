<?php

namespace App\Domain\Issuer\Services;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Models\SigningKey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenSSLAsymmetricKey;
use Throwable;

final class SigningKeyService
{
    public function requireActiveKey(): SigningKey
    {
        Log::debug('[issuer.signing_key.require_active] started');

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
            ->latest('id')
            ->first();

        if ($key === null) {
            Log::warning('[issuer.signing_key.require_active] missing_active_key');

            throw SigningKeyStateException::missingActiveKey();
        }

        Log::debug('[issuer.signing_key.require_active] completed', [
            'key_id' => $key->id,
            'kid' => $key->kid,
        ]);

        return $key;
    }

    public function createActiveKey(): SigningKey
    {
        return $this->createKey(SigningKeyStatus::Active, CarbonImmutable::now());
    }

    public function prepareNextKey(): SigningKey
    {
        Log::debug('[issuer.signing_key.prepare_next] started');

        /** @var SigningKey|null $existing */
        $existing = SigningKey::query()
            ->where('status', SigningKeyStatus::Next->value)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            Log::info('[issuer.signing_key.prepare_next] already_exists', [
                'key_id' => $existing->id,
                'kid' => $existing->kid,
            ]);

            return $existing;
        }

        return $this->createKey(SigningKeyStatus::Next, null);
    }

    public function decryptPrivateKey(SigningKey $signingKey): string
    {
        try {
            return Crypt::decryptString($signingKey->private_key_encrypted);
        } catch (Throwable $exception) {
            Log::error('[issuer.signing_key.decrypt] failed', [
                'key_id' => $signingKey->id,
                'kid' => $signingKey->kid,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]);

            throw SigningKeyStateException::privateKeyDecryptFailed();
        }
    }

    private function createKey(SigningKeyStatus $status, ?CarbonImmutable $activatedAt): SigningKey
    {
        $algorithm = $this->resolveAlgorithm();

        if ($algorithm !== 'RS256') {
            throw SigningKeyStateException::unsupportedAlgorithm($algorithm);
        }

        Log::info('[issuer.signing_key.create] started', [
            'status' => $status->value,
            'algorithm' => $algorithm,
        ]);

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

        Log::info('[issuer.signing_key.create] completed', [
            'key_id' => $key->id,
            'kid' => $key->kid,
            'status' => $status->value,
        ]);

        return $key;
    }

    private function resolveAlgorithm(): string
    {
        $algorithms = config('issuer.allowed_algs', ['RS256']);
        $algorithm = (string) ($algorithms[0] ?? 'RS256');

        return trim($algorithm) !== '' ? trim($algorithm) : 'RS256';
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
            Log::error('[issuer.signing_key.generate] openssl_pkey_new_failed', [
                'bits' => $bits,
                'openssl_errors' => $this->collectOpenSslErrors(),
            ]);

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
            Log::error('[issuer.signing_key.generate] openssl_key_export_failed', [
                'bits' => $bits,
                'exported' => $exported,
                'openssl_errors' => $this->collectOpenSslErrors(),
            ]);

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
