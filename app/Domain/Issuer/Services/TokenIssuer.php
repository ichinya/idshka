<?php

namespace App\Domain\Issuer\Services;

use App\Contracts\Auth\JwtClaims;
use App\Contracts\Auth\JwtHeaders;
use App\Contracts\Auth\OidcScopes;
use App\Domain\Issuer\DTO\IssuedUserApiToken;
use App\Domain\Issuer\DTO\IssuedWebLoginTokens;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Models\SigningKey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class TokenIssuer
{
    public function __construct(
        private readonly SigningKeyService $signingKeyService,
    ) {}

    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $permissions
     */
    public function issueUserApiToken(
        int $userId,
        string $siteId,
        string $audience,
        array $scopes,
        array $permissions,
        ?CarbonImmutable $expiresAt = null,
        bool $doesNotExpire = false,
    ): IssuedUserApiToken {
        Log::info('[issuer.token.issue] started', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'audience' => $audience,
            'requested_scopes_count' => count($scopes),
            'requested_permissions_count' => count($permissions),
        ]);

        $signingKey = $this->signingKeyService->requireActiveKey();

        if (trim($signingKey->kid) === '') {
            throw SigningKeyStateException::invalidState();
        }

        $allowedAlgorithms = config('issuer.allowed_algs', ['RS256']);

        if (! in_array($signingKey->algorithm, $allowedAlgorithms, true)) {
            throw SigningKeyStateException::unsupportedAlgorithm($signingKey->algorithm);
        }

        $issuedAt = CarbonImmutable::now();
        $notBefore = $issuedAt;
        $resolvedExpiresAt = $doesNotExpire
            ? null
            : ($expiresAt?->setTimezone('UTC') ?? $issuedAt->addSeconds(max(1, (int) config('issuer.user_api_token_ttl_seconds', 900))));
        $jti = (string) Str::uuid();
        $subject = (string) $userId;

        $claims = new JwtClaims(
            issuer: (string) config('issuer.issuer'),
            audience: $audience,
            subject: $subject,
            siteId: $siteId,
            tokenType: JwtClaims::TOKEN_TYPE_USER_API,
            scopes: $scopes,
            permissions: $permissions,
            jti: $jti,
            issuedAt: $issuedAt->getTimestamp(),
            notBefore: $notBefore->getTimestamp(),
            expiresAt: $resolvedExpiresAt?->getTimestamp(),
        );

        $privatePem = $this->signingKeyService->decryptPrivateKey($signingKey);

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privatePem),
            InMemory::plainText($signingKey->public_key_pem),
        );

        $builder = $configuration->builder()
            ->issuedBy($claims->issuer)
            ->permittedFor($claims->audience)
            ->relatedTo($claims->subject)
            ->identifiedBy($claims->jti)
            ->issuedAt($issuedAt->toDateTimeImmutable())
            ->canOnlyBeUsedAfter($notBefore->toDateTimeImmutable())
            ->withHeader(JwtHeaders::ALG, $signingKey->algorithm)
            ->withHeader(JwtHeaders::KID, $signingKey->kid)
            ->withHeader(JwtHeaders::TYP, JwtHeaders::TYP_VALUE)
            ->withClaim('site_id', $claims->siteId)
            ->withClaim('token_type', $claims->tokenType)
            ->withClaim('scope', implode(' ', $claims->scopes))
            ->withClaim('permissions', $claims->permissions);

        if ($resolvedExpiresAt !== null) {
            $builder = $builder->expiresAt($resolvedExpiresAt->toDateTimeImmutable());
        }

        $token = $builder->getToken($configuration->signer(), $configuration->signingKey());

        Log::info('[issuer.token.issue] completed', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'audience' => $audience,
            'jti' => $jti,
            'kid' => $signingKey->kid,
            'expires_at' => $resolvedExpiresAt?->toISOString(),
        ]);

        return new IssuedUserApiToken(
            rawToken: $token->toString(),
            jti: $jti,
            kid: $signingKey->kid,
            audience: $audience,
            scopes: $scopes,
            permissions: $permissions,
            issuedAt: $issuedAt,
            notBefore: $notBefore,
            expiresAt: $resolvedExpiresAt,
            signingKeyId: $signingKey->id,
        );
    }

    /**
     * @param  list<string>  $scopes
     */
    public function issueWebLoginTokens(
        int $userId,
        string $siteId,
        string $clientId,
        array $scopes,
        string $nonce,
        string $name,
        string $email,
    ): IssuedWebLoginTokens {
        Log::info('[issuer.web_login.issue] started', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'client_id' => $clientId,
            'scopes_count' => count($scopes),
        ]);

        $signingKey = $this->requireUsableSigningKey();
        $configuration = $this->configurationFor($signingKey);
        $issuedAt = CarbonImmutable::now();
        $notBefore = $issuedAt;
        $idTokenExpiresAt = $issuedAt->addSeconds(max(1, (int) config('issuer.id_token_ttl_seconds', 300)));
        $accessTokenExpiresAt = $issuedAt->addSeconds(max(1, (int) config('issuer.web_access_token_ttl_seconds', 600)));
        $idTokenJti = (string) Str::uuid();
        $accessTokenJti = (string) Str::uuid();

        $idTokenBuilder = $configuration->builder()
            ->issuedBy((string) config('issuer.issuer'))
            ->permittedFor($clientId)
            ->relatedTo((string) $userId)
            ->identifiedBy($idTokenJti)
            ->issuedAt($issuedAt->toDateTimeImmutable())
            ->canOnlyBeUsedAfter($notBefore->toDateTimeImmutable())
            ->expiresAt($idTokenExpiresAt->toDateTimeImmutable())
            ->withHeader(JwtHeaders::ALG, $signingKey->algorithm)
            ->withHeader(JwtHeaders::KID, $signingKey->kid)
            ->withHeader(JwtHeaders::TYP, JwtHeaders::TYP_VALUE)
            ->withClaim('site_id', $siteId)
            ->withClaim('client_id', $clientId)
            ->withClaim('token_type', JwtClaims::TOKEN_TYPE_ID_TOKEN)
            ->withClaim('scope', implode(' ', $scopes))
            ->withClaim('nonce', $nonce);

        if (in_array(OidcScopes::PROFILE, $scopes, true)) {
            $idTokenBuilder = $idTokenBuilder->withClaim('name', $name);
        }

        if (in_array(OidcScopes::EMAIL, $scopes, true)) {
            $idTokenBuilder = $idTokenBuilder->withClaim('email', $email);
        }

        $idToken = $idTokenBuilder->getToken($configuration->signer(), $configuration->signingKey());

        $accessToken = $configuration->builder()
            ->issuedBy((string) config('issuer.issuer'))
            ->permittedFor($clientId)
            ->relatedTo((string) $userId)
            ->identifiedBy($accessTokenJti)
            ->issuedAt($issuedAt->toDateTimeImmutable())
            ->canOnlyBeUsedAfter($notBefore->toDateTimeImmutable())
            ->expiresAt($accessTokenExpiresAt->toDateTimeImmutable())
            ->withHeader(JwtHeaders::ALG, $signingKey->algorithm)
            ->withHeader(JwtHeaders::KID, $signingKey->kid)
            ->withHeader(JwtHeaders::TYP, JwtHeaders::TYP_VALUE)
            ->withClaim('site_id', $siteId)
            ->withClaim('client_id', $clientId)
            ->withClaim('token_type', JwtClaims::TOKEN_TYPE_WEB_ACCESS)
            ->withClaim('scope', implode(' ', $scopes))
            ->getToken($configuration->signer(), $configuration->signingKey());

        Log::info('[issuer.web_login.issue] completed', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'client_id' => $clientId,
            'id_token_jti' => $idTokenJti,
            'access_token_jti' => $accessTokenJti,
            'kid' => $signingKey->kid,
            'access_token_expires_at' => $accessTokenExpiresAt->toISOString(),
        ]);

        return new IssuedWebLoginTokens(
            rawIdToken: $idToken->toString(),
            rawAccessToken: $accessToken->toString(),
            idTokenJti: $idTokenJti,
            accessTokenJti: $accessTokenJti,
            kid: $signingKey->kid,
            scopes: $scopes,
            idTokenExpiresAt: $idTokenExpiresAt,
            accessTokenExpiresAt: $accessTokenExpiresAt,
        );
    }

    private function requireUsableSigningKey(): SigningKey
    {
        $signingKey = $this->signingKeyService->requireActiveKey();

        if (trim($signingKey->kid) === '') {
            throw SigningKeyStateException::invalidState();
        }

        $allowedAlgorithms = config('issuer.allowed_algs', ['RS256']);

        if (! in_array($signingKey->algorithm, $allowedAlgorithms, true)) {
            throw SigningKeyStateException::unsupportedAlgorithm($signingKey->algorithm);
        }

        return $signingKey;
    }

    private function configurationFor(SigningKey $signingKey): Configuration
    {
        $privatePem = $this->signingKeyService->decryptPrivateKey($signingKey);

        return Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privatePem),
            InMemory::plainText($signingKey->public_key_pem),
        );
    }
}
