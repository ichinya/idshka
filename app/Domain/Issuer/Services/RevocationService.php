<?php

namespace App\Domain\Issuer\Services;

use App\Domain\Issuer\Events\UserApiTokenRevoked;
use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Models\RevokedJti;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RevocationService
{
    public function revokeForUser(int $userId, ApiToken $apiToken): ApiToken
    {
        Log::info('[issuer.revoke] started', [
            'api_token_id' => $apiToken->id,
            'user_id' => $userId,
            'site_id' => $apiToken->site_id,
            'audience' => $apiToken->audience,
            'jti' => $apiToken->jti,
            'already_revoked' => $apiToken->isRevoked(),
        ]);

        if ($apiToken->user_id !== $userId) {
            Log::warning('[issuer.revoke] owner_mismatch', [
                'api_token_id' => $apiToken->id,
                'user_id' => $userId,
                'token_owner_user_id' => $apiToken->user_id,
            ]);

            throw IssuerFlowException::forbidden('token_owner_mismatch', 'You cannot revoke this token.');
        }

        if ($apiToken->isRevoked()) {
            Log::info('[issuer.revoke] idempotent_already_revoked', [
                'api_token_id' => $apiToken->id,
                'jti' => $apiToken->jti,
                'revoked_at' => $apiToken->revoked_at?->toISOString(),
            ]);

            return $apiToken;
        }

        $revokedAt = CarbonImmutable::now();

        DB::transaction(function () use ($apiToken, $revokedAt): void {
            $apiToken->update([
                'revoked_at' => $revokedAt->toDateTimeString(),
            ]);

            RevokedJti::query()->firstOrCreate(
                [
                    'jti' => $apiToken->jti,
                ],
                [
                    'api_token_id' => $apiToken->id,
                    'user_id' => $apiToken->user_id,
                    'site_id' => $apiToken->site_id,
                    'audience' => $apiToken->audience,
                    'expires_at' => $apiToken->expires_at->toDateTimeString(),
                    'revoked_at' => $revokedAt->toDateTimeString(),
                ],
            );
        });

        $this->cacheRevokedJti($apiToken);

        UserApiTokenRevoked::dispatch(
            apiTokenId: $apiToken->id,
            userId: $apiToken->user_id,
            siteId: $apiToken->site_id,
            audience: $apiToken->audience,
            jti: $apiToken->jti,
            revokedAt: $revokedAt,
            expiresAt: CarbonImmutable::instance($apiToken->expires_at),
        );

        $freshToken = $apiToken->refresh();

        Log::info('[issuer.revoke] completed', [
            'api_token_id' => $apiToken->id,
            'user_id' => $userId,
            'jti' => $apiToken->jti,
            'revoked_at' => $freshToken->revoked_at?->toISOString(),
        ]);

        return $freshToken;
    }

    private function cacheRevokedJti(ApiToken $apiToken): void
    {
        $enabled = (bool) config('issuer.revocation.redis_denylist_enabled', true);

        if (! $enabled) {
            Log::debug('[issuer.revoke.cache_denylist] disabled');

            return;
        }

        $ttlSeconds = CarbonImmutable::now()->diffInSeconds(
            CarbonImmutable::instance($apiToken->expires_at),
            false,
        );

        if ($ttlSeconds <= 0) {
            return;
        }

        $cachePrefix = (string) config('issuer.revocation.cache_prefix', 'issuer:denylist:jti:');
        $cacheKey = $cachePrefix.$apiToken->jti;

        Cache::put($cacheKey, true, max(1, $ttlSeconds));

        Log::debug('[issuer.revoke.cache_denylist] updated', [
            'cache_key' => $cacheKey,
            'ttl_seconds' => $ttlSeconds,
        ]);
    }
}
