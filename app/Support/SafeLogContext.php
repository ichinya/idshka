<?php

namespace App\Support;

use Illuminate\Http\Request;
use Throwable;

final class SafeLogContext
{
    private const REDACTED = '[redacted]';

    private const SECRET_KEY_FRAGMENTS = [
        'authorization_code',
        'client_secret',
        'code_verifier',
        'error_message',
        'private_key',
        'raw_token',
        'password',
        'refresh_token',
        'access_token',
        'id_token',
    ];

    private const SECRET_KEYS = [
        'authorization',
        'code',
        'secret',
        'token',
    ];

    private const SAFE_KEYS = [
        'access_token_expires_at',
        'access_token_jti',
        'api_token_id',
        'cache_key',
        'code_hash_prefix',
        'custom_expires_at',
        'expires_at',
        'id_token_jti',
        'jti',
        'kid',
        'metadata_keys',
        'request_id',
        'revoked_at',
        'token_hash',
        'token_id',
        'token_type',
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function from(array $context = []): array
    {
        $context = self::redact($context);

        if (! array_key_exists('request_id', $context)) {
            $requestId = self::currentRequestId();

            if ($requestId !== null) {
                $context = ['request_id' => $requestId] + $context;
            }
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            $normalizedKey = self::normalizeKey((string) $key);

            if (self::isSecretKey($normalizedKey)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? self::redactNested($value) : $value;
        }

        return $redacted;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private static function redactNested(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && self::isSecretKey(self::normalizeKey($key))) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? self::redactNested($value) : $value;
        }

        return $redacted;
    }

    private static function isSecretKey(string $normalizedKey): bool
    {
        if (in_array($normalizedKey, self::SAFE_KEYS, true)) {
            return false;
        }

        if (in_array($normalizedKey, self::SECRET_KEYS, true)) {
            return true;
        }

        foreach (self::SECRET_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeKey(string $key): string
    {
        return strtolower(str_replace(['-', ' '], '_', $key));
    }

    private static function currentRequestId(): ?string
    {
        try {
            $request = app(Request::class);
        } catch (Throwable) {
            return null;
        }

        $requestId = $request->attributes->get('request_id');

        if (! is_string($requestId) || trim($requestId) === '') {
            return null;
        }

        return $requestId;
    }
}
