<?php

use App\Contracts\Auth\JwtClaims;
use App\Contracts\Auth\JwtHeaders;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Services\SigningKeyService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('idshka:gateway-smoke-token
    {--audience=apishka.ru : JWT aud claim; comma-separated values create an array audience}
    {--site-id=site_smoke_apishka : JWT site_id claim}
    {--user-id=1 : JWT sub claim}
    {--expires-offset=900 : Seconds from now for exp; negative values produce expired tokens}
    {--not-before-offset=0 : Seconds from now for nbf}', function () {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('The gateway smoke token command is available only in local/testing environments.');

        return 1;
    }

    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);

    try {
        $signingKey = $signingKeyService->requireActiveKey();
    } catch (SigningKeyStateException) {
        $signingKey = $signingKeyService->createActiveKey();
    }

    $issuedAt = CarbonImmutable::now();
    $notBefore = $issuedAt->addSeconds((int) $this->option('not-before-offset'));
    $expiresAt = $issuedAt->addSeconds((int) $this->option('expires-offset'));
    $audiences = array_values(array_filter(array_map(
        static fn (string $audience): string => trim($audience),
        explode(',', (string) $this->option('audience')),
    )));

    if ($audiences === []) {
        $audiences = ['apishka.ru'];
    }

    $privatePem = $signingKeyService->decryptPrivateKey($signingKey);

    $configuration = Configuration::forAsymmetricSigner(
        new Sha256,
        InMemory::plainText($privatePem),
        InMemory::plainText($signingKey->public_key_pem),
    );

    $token = $configuration->builder()
        ->issuedBy((string) config('issuer.issuer'))
        ->permittedFor(...$audiences)
        ->relatedTo((string) $this->option('user-id'))
        ->identifiedBy((string) Str::uuid())
        ->issuedAt($issuedAt->toDateTimeImmutable())
        ->canOnlyBeUsedAfter($notBefore->toDateTimeImmutable())
        ->expiresAt($expiresAt->toDateTimeImmutable())
        ->withHeader(JwtHeaders::ALG, $signingKey->algorithm)
        ->withHeader(JwtHeaders::KID, $signingKey->kid)
        ->withHeader(JwtHeaders::TYP, JwtHeaders::TYP_VALUE)
        ->withClaim('site_id', (string) $this->option('site-id'))
        ->withClaim('token_type', JwtClaims::TOKEN_TYPE_USER_API)
        ->withClaim('scope', 'orders.read')
        ->withClaim('permissions', ['orders.read'])
        ->getToken($configuration->signer(), $configuration->signingKey());

    $this->line($token->toString());

    return 0;
})->purpose('Issue a local-only JWT for OpenResty gateway smoke tests.');
