<?php

namespace App\Domain\Issuer\Services;

use App\Domain\Issuer\DTO\IssuedAuthorizationCode;
use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Models\OAuthAuthorizationCode;
use App\Domain\OidcClients\DTO\ResolvedOidcClient;
use App\Domain\OidcClients\Models\OidcClient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AuthorizationCodeService
{
    /**
     * @param  list<string>  $scopes
     */
    public function issue(
        ResolvedOidcClient $resolvedClient,
        User $user,
        array $scopes,
        string $nonce,
        string $codeChallenge,
        string $codeChallengeMethod,
    ): IssuedAuthorizationCode {
        $rawCode = Str::random(72);
        $codeHash = hash('sha256', $rawCode);
        $expiresAt = CarbonImmutable::now()->addSeconds(
            max(1, (int) config('issuer.authorization_code_ttl_seconds', 300)),
        );

        Log::info('[issuer.authorization_code.issue] started', [
            'client_id' => $resolvedClient->client->client_id,
            'site_id' => $resolvedClient->site->id,
            'user_id' => $user->id,
            'scopes_count' => count($scopes),
            'code_hash_prefix' => substr($codeHash, 0, 12),
        ]);

        /** @var OAuthAuthorizationCode $record */
        $record = OAuthAuthorizationCode::query()->create([
            'oidc_client_id' => $resolvedClient->client->id,
            'user_id' => $user->id,
            'site_id' => $resolvedClient->site->id,
            'code_hash' => $codeHash,
            'redirect_uri' => $resolvedClient->redirectUri->redirect_uri,
            'scopes' => $scopes,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'expires_at' => $expiresAt->toDateTimeString(),
            'consumed_at' => null,
        ]);

        Log::info('[issuer.authorization_code.issue] completed', [
            'authorization_code_id' => $record->id,
            'client_id' => $resolvedClient->client->client_id,
            'site_id' => $resolvedClient->site->id,
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return new IssuedAuthorizationCode($rawCode, $record, $expiresAt);
    }

    public function consume(
        OidcClient $client,
        string $code,
        string $redirectUri,
        string $codeVerifier,
        PkceService $pkceService,
    ): OAuthAuthorizationCode {
        $codeHash = hash('sha256', $code);

        Log::info('[issuer.authorization_code.consume] started', [
            'client_id' => $client->client_id,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
            'code_hash_prefix' => substr($codeHash, 0, 12),
        ]);

        return DB::transaction(function () use ($client, $codeHash, $redirectUri, $codeVerifier, $pkceService): OAuthAuthorizationCode {
            /** @var OAuthAuthorizationCode|null $record */
            $record = OAuthAuthorizationCode::query()
                ->where('code_hash', $codeHash)
                ->where('oidc_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                Log::warning('[issuer.authorization_code.consume] missing_code', [
                    'client_id' => $client->client_id,
                    'code_hash_prefix' => substr($codeHash, 0, 12),
                ]);

                throw IssuerFlowException::unauthorized('invalid_grant', 'Invalid authorization code.');
            }

            if ($record->consumed_at !== null || $record->expires_at->isPast()) {
                Log::warning('[issuer.authorization_code.consume] inactive_code', [
                    'authorization_code_id' => $record->id,
                    'client_id' => $client->client_id,
                    'is_consumed' => $record->consumed_at !== null,
                    'expires_at' => $record->expires_at->toISOString(),
                ]);

                throw IssuerFlowException::unauthorized('invalid_grant', 'Invalid authorization code.');
            }

            if ($record->redirect_uri !== $redirectUri) {
                Log::warning('[issuer.authorization_code.consume] redirect_uri_mismatch', [
                    'authorization_code_id' => $record->id,
                    'client_id' => $client->client_id,
                    'redirect_uri_hash' => hash('sha256', $redirectUri),
                ]);

                throw IssuerFlowException::unauthorized('invalid_grant', 'Invalid authorization code.');
            }

            if (! $pkceService->verify($codeVerifier, $record->code_challenge, $record->code_challenge_method)) {
                Log::warning('[issuer.authorization_code.consume] pkce_failed', [
                    'authorization_code_id' => $record->id,
                    'client_id' => $client->client_id,
                ]);

                throw IssuerFlowException::unauthorized('invalid_grant', 'Invalid authorization code.');
            }

            $record->forceFill(['consumed_at' => now()])->save();

            Log::info('[issuer.authorization_code.consume] completed', [
                'authorization_code_id' => $record->id,
                'client_id' => $client->client_id,
                'site_id' => $record->site_id,
                'user_id' => $record->user_id,
            ]);

            return $record->refresh();
        });
    }
}
