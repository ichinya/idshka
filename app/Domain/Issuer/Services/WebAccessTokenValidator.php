<?php

namespace App\Domain\Issuer\Services;

use App\Contracts\Auth\JwtClaims;
use App\Domain\Issuer\DTO\ValidatedWebAccessToken;
use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Models\SigningKey;
use App\Support\SafeLogContext;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Symfony\Component\Clock\NativeClock;
use Throwable;

final class WebAccessTokenValidator
{
    public function validate(string $rawToken): ValidatedWebAccessToken
    {
        try {
            $parserConfiguration = Configuration::forAsymmetricSigner(
                new Sha256,
                InMemory::plainText('unused'),
                InMemory::plainText('unused'),
            );
            $token = $parserConfiguration->parser()->parse($rawToken);
        } catch (Throwable $exception) {
            Log::warning('[issuer.web_access.validate] parse_failed', SafeLogContext::from([
                'error_class' => $exception::class,
            ]));

            throw IssuerFlowException::unauthorized('invalid_token', 'Invalid access token.');
        }

        if (! $token instanceof UnencryptedToken) {
            throw IssuerFlowException::unauthorized('invalid_token', 'Invalid access token.');
        }

        $kid = (string) $token->headers()->get('kid', '');

        /** @var SigningKey|null $signingKey */
        $signingKey = SigningKey::query()->where('kid', $kid)->first();

        if ($signingKey === null) {
            Log::warning('[issuer.web_access.validate] unknown_kid', SafeLogContext::from([
                'kid' => $kid,
            ]));

            throw IssuerFlowException::unauthorized('invalid_token', 'Invalid access token.');
        }

        $validationConfiguration = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($signingKey->public_key_pem),
            InMemory::plainText($signingKey->public_key_pem),
        );

        $constraints = [
            new SignedWith($validationConfiguration->signer(), $validationConfiguration->verificationKey()),
            new IssuedBy((string) config('issuer.issuer')),
            new StrictValidAt(new NativeClock(new DateTimeZone('UTC'))),
        ];

        if (! $validationConfiguration->validator()->validate($token, ...$constraints)) {
            Log::warning('[issuer.web_access.validate] constraints_failed', SafeLogContext::from([
                'kid' => $kid,
            ]));

            throw IssuerFlowException::unauthorized('invalid_token', 'Invalid access token.');
        }

        if ($token->claims()->get('token_type') !== JwtClaims::TOKEN_TYPE_WEB_ACCESS) {
            Log::warning('[issuer.web_access.validate] unsupported_token_type', SafeLogContext::from([
                'jti' => (string) $token->claims()->get('jti', ''),
                'token_type' => (string) $token->claims()->get('token_type', ''),
            ]));

            throw IssuerFlowException::unauthorized('invalid_token', 'Invalid access token.');
        }

        $scopes = array_values(array_filter(explode(' ', (string) $token->claims()->get('scope', ''))));
        $clientId = (string) $token->claims()->get('client_id', '');

        Log::debug('[issuer.web_access.validate] completed', SafeLogContext::from([
            'jti' => (string) $token->claims()->get('jti'),
            'client_id' => $clientId,
            'site_id' => (string) $token->claims()->get('site_id'),
            'scopes_count' => count($scopes),
        ]));

        return new ValidatedWebAccessToken(
            userId: (int) $token->claims()->get('sub'),
            siteId: (string) $token->claims()->get('site_id'),
            clientId: $clientId,
            scopes: $scopes,
            jti: (string) $token->claims()->get('jti'),
        );
    }
}
