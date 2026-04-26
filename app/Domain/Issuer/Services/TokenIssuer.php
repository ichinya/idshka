<?php

namespace App\Domain\Issuer\Services;

use App\Contracts\Auth\JwtClaims;
use App\Contracts\Auth\JwtHeaders;
use App\Domain\Issuer\DTO\IssuedUserApiToken;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
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
        $expiresAt = $issuedAt->addSeconds(max(1, (int) config('issuer.user_api_token_ttl_seconds', 900)));
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
            expiresAt: $expiresAt->getTimestamp(),
        );

        $privatePem = $this->signingKeyService->decryptPrivateKey($signingKey);

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privatePem),
            InMemory::plainText($signingKey->public_key_pem),
        );

        $token = $configuration->builder()
            ->issuedBy($claims->issuer)
            ->permittedFor($claims->audience)
            ->relatedTo($claims->subject)
            ->identifiedBy($claims->jti)
            ->issuedAt($issuedAt->toDateTimeImmutable())
            ->canOnlyBeUsedAfter($notBefore->toDateTimeImmutable())
            ->expiresAt($expiresAt->toDateTimeImmutable())
            ->withHeader(JwtHeaders::ALG, $signingKey->algorithm)
            ->withHeader(JwtHeaders::KID, $signingKey->kid)
            ->withHeader(JwtHeaders::TYP, JwtHeaders::TYP_VALUE)
            ->withClaim('site_id', $claims->siteId)
            ->withClaim('token_type', $claims->tokenType)
            ->withClaim('scope', implode(' ', $claims->scopes))
            ->withClaim('permissions', $claims->permissions)
            ->getToken($configuration->signer(), $configuration->signingKey());

        Log::info('[issuer.token.issue] completed', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'audience' => $audience,
            'jti' => $jti,
            'kid' => $signingKey->kid,
            'expires_at' => $expiresAt->toISOString(),
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
            expiresAt: $expiresAt,
            signingKeyId: $signingKey->id,
        );
    }
}
