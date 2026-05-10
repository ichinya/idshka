<?php

use App\Contracts\Auth\JwtClaims;
use App\Contracts\Auth\JwtHeaders;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Support\SafeLogContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\Console\Output\BufferedOutput;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$containsDestructiveMigrationSql = static function (string $sql): bool {
    return preg_match(<<<'REGEX'
~
    \btruncate\b
    |\bdelete\s+from\b
    |\bdrop\s+(?:database|schema|table|view|materialized\s+view|index|sequence|type|function|procedure|trigger)\b
    |\balter\s+table\b[^\n;]*\bdrop\s+(?:column|constraint|foreign\s+key|primary\s+key|index)\b
~ix
REGEX, $sql) === 1;
};

Artisan::command('idshka:runtime-migrate
    {--database= : The database connection to use}
    {--pretend : Dump the SQL queries that would be run}', function () use ($containsDestructiveMigrationSql) {
    /** @var Migrator $migrator */
    $migrator = app(Migrator::class);
    $database = (string) ($this->option('database') ?? '');
    $database = $database === '' ? null : $database;
    $pretend = (bool) $this->option('pretend');
    $paths = array_merge($migrator->paths(), [database_path('migrations')]);
    $isProduction = app()->environment('production');

    Log::info('[FIX:runtime-migrate] started', SafeLogContext::from([
        'database' => $database ?? 'default',
        'pretend' => $pretend,
        'production' => $isProduction,
    ]));

    $ran = $migrator->usingConnection($database, function () use ($migrator, $database, $paths, $pretend, $isProduction, $containsDestructiveMigrationSql): ?array {
        if (! $migrator->repositoryExists()) {
            $this->components->info('Preparing database.');

            $installed = $this->callSilent('migrate:install', array_filter([
                '--database' => $database,
            ], static fn ($value): bool => $value !== null));

            if ($installed !== 0) {
                Log::error('[FIX:runtime-migrate] migration_repository_install_failed', SafeLogContext::from([
                    'database' => $database ?? 'default',
                ]));

                throw new RuntimeException('Migration repository installation failed.');
            }
        }

        if ($isProduction && ! $pretend) {
            $preview = new BufferedOutput;
            $migrator
                ->setOutput($preview)
                ->run($paths, [
                    'pretend' => true,
                    'step' => false,
                ]);

            if ($containsDestructiveMigrationSql($preview->fetch())) {
                Log::error('[FIX:runtime-migrate] blocked_destructive_sql', SafeLogContext::from([
                    'database' => $database ?? 'default',
                ]));

                $this->error('Production runtime migration blocked: destructive SQL detected in pending migrations.');

                return null;
            }
        }

        return $migrator
            ->setOutput($this->output)
            ->run($paths, [
                'pretend' => $pretend,
                'step' => false,
            ]);
    });

    if ($ran === null) {
        return 1;
    }

    Log::info('[FIX:runtime-migrate] completed', SafeLogContext::from([
        'database' => $database ?? 'default',
        'pretend' => $pretend,
        'production' => $isProduction,
        'migrations_count' => count($ran),
    ]));

    return 0;
})->purpose('Run runtime migrations from containers without Laravel production --force confirmation.');

Artisan::command('idshka:keys:status', function () {
    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);
    $rows = $signingKeyService->statusReport();

    $this->info('Signing keys');

    foreach ($rows as $row) {
        $this->line(sprintf(
            '- id=%d kid=%s status=%s signing=%s jwks=%s blockers=%d activated_at=%s retired_at=%s',
            $row['key_id'],
            $row['kid'],
            $row['status'],
            $row['is_signing'] ? 'yes' : 'no',
            $row['jwks_published'] ? 'yes' : 'no',
            $row['blocking_api_tokens_count'],
            $row['activated_at'] ?? 'null',
            $row['retired_at'] ?? 'null',
        ));
    }

    Log::info('[issuer.signing_key.command.status] completed', SafeLogContext::from([
        'keys_count' => count($rows),
    ]));

    return 0;
})->purpose('List issuer signing keys and safe lifecycle state.');

Artisan::command('idshka:keys:prepare', function () {
    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);
    $key = $signingKeyService->prepareNextKey();

    $this->info("Prepared signing key {$key->kid}");

    Log::info('[issuer.signing_key.command.prepare] completed', SafeLogContext::from([
        'key_id' => $key->id,
        'kid' => $key->kid,
        'status' => $key->status,
    ]));

    return 0;
})->purpose('Prepare the next issuer signing key.');

Artisan::command('idshka:keys:activate-next', function () {
    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);

    try {
        $key = $signingKeyService->activateNextKey();
    } catch (SigningKeyStateException $exception) {
        $this->error('No prepared signing key is available to activate.');

        Log::warning('[issuer.signing_key.command.activate_next] failed', SafeLogContext::from([
            'error_class' => $exception::class,
        ]));

        return 1;
    }

    $this->info("Activated signing key {$key->kid}");

    Log::info('[issuer.signing_key.command.activate_next] completed', SafeLogContext::from([
        'key_id' => $key->id,
        'kid' => $key->kid,
        'status' => $key->status,
    ]));

    return 0;
})->purpose('Activate the prepared next issuer signing key.');

Artisan::command('idshka:keys:retire-expired {--dry-run : Show retirable keys without mutating state}', function () {
    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);
    $dryRun = (bool) $this->option('dry-run');
    $result = $signingKeyService->retireExpiredKeys($dryRun);

    if ($dryRun) {
        $this->info("Retirable signing keys: {$result['retirable_count']}");
    } else {
        $this->info("Retired signing keys: {$result['retired_count']}");
    }

    if ($result['blocked_count'] > 0) {
        $this->warn("Blocked API token blockers: {$result['blocked_count']}");
    }

    Log::info('[issuer.signing_key.command.retire_expired] completed', SafeLogContext::from([
        'dry_run' => $dryRun,
        'retirable_count' => $result['retirable_count'],
        'retired_count' => $result['retired_count'],
        'blocked_count' => $result['blocked_count'],
    ]));

    return 0;
})->purpose('Retire old active issuer signing keys after token TTLs expire.');

Artisan::command('idshka:keys:force-retire
    {kid : Public key id to retire}
    {--dry-run : Show the target without mutating state}
    {--force : Confirm destructive key retirement}', function () {
    $kid = (string) $this->argument('kid');
    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');

    if (! $dryRun && ! $force) {
        $this->error('Force retirement is destructive. Re-run with --force or use --dry-run.');

        Log::warning('[issuer.signing_key.command.force_retire] refused_without_force', SafeLogContext::from([
            'kid' => $kid,
        ]));

        return 1;
    }

    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);

    if ($dryRun) {
        $rows = array_values(array_filter(
            $signingKeyService->statusReport(),
            static fn (array $row): bool => $row['kid'] === $kid,
        ));

        if ($rows === []) {
            $this->error("Signing key not found: {$kid}");

            return 1;
        }

        $this->info("Would force retire signing key {$kid}");

        Log::info('[issuer.signing_key.command.force_retire] dry_run_completed', SafeLogContext::from([
            'kid' => $kid,
        ]));

        return 0;
    }

    try {
        $key = $signingKeyService->forceRetireByKid($kid);
    } catch (SigningKeyStateException $exception) {
        $this->error("Signing key not found: {$kid}");

        Log::warning('[issuer.signing_key.command.force_retire] failed', SafeLogContext::from([
            'kid' => $kid,
            'error_class' => $exception::class,
        ]));

        return 1;
    }

    $this->info("Force retired signing key {$key->kid}");

    Log::warning('[issuer.signing_key.command.force_retire] completed', SafeLogContext::from([
        'key_id' => $key->id,
        'kid' => $key->kid,
        'status' => $key->status,
    ]));

    return 0;
})->purpose('Force retire a compromised issuer signing key by kid.');

Artisan::command('idshka:keys:rollback
    {--dry-run : Show the rollback target without mutating state}
    {--force : Confirm destructive key rollback}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');

    if (! $dryRun && ! $force) {
        $this->error('Rollback is destructive. Re-run with --force or use --dry-run.');

        Log::warning('[issuer.signing_key.command.rollback] refused_without_force', SafeLogContext::from());

        return 1;
    }

    /** @var SigningKeyService $signingKeyService */
    $signingKeyService = app(SigningKeyService::class);

    if ($dryRun) {
        $activeRows = array_values(array_filter(
            $signingKeyService->statusReport(),
            static fn (array $row): bool => $row['status'] === 'active' && $row['jwks_published'] === true,
        ));

        usort(
            $activeRows,
            static fn (array $left, array $right): int => $right['key_id'] <=> $left['key_id'],
        );

        $newest = $activeRows[0] ?? null;
        $previous = $activeRows[1] ?? null;

        if (! is_array($newest) || ! is_array($previous)) {
            $this->error('Rollback requires at least two active signing keys.');

            Log::warning('[issuer.signing_key.command.rollback] dry_run_failed', SafeLogContext::from([
                'active_keys_count' => count($activeRows),
            ]));

            return 1;
        }

        $this->info("Would rollback signing from {$newest['kid']} to {$previous['kid']}");

        Log::info('[issuer.signing_key.command.rollback] dry_run_completed', SafeLogContext::from([
            'retiring_key_id' => $newest['key_id'],
            'retiring_kid' => $newest['kid'],
            'active_key_id' => $previous['key_id'],
            'kid' => $previous['kid'],
        ]));

        return 0;
    }

    try {
        $key = $signingKeyService->rollbackToPreviousActive();
    } catch (SigningKeyStateException $exception) {
        $this->error('Rollback requires at least two active signing keys.');

        Log::warning('[issuer.signing_key.command.rollback] failed', SafeLogContext::from([
            'error_class' => $exception::class,
        ]));

        return 1;
    }

    $this->info("Rolled back signing key to {$key->kid}");

    Log::warning('[issuer.signing_key.command.rollback] completed', SafeLogContext::from([
        'key_id' => $key->id,
        'kid' => $key->kid,
        'status' => $key->status,
    ]));

    return 0;
})->purpose('Rollback issuer signing to the previous active key.');

Artisan::command('idshka:gateway-smoke-token
    {--audience=example.test : JWT aud claim; comma-separated values create an array audience}
    {--site-id=site_smoke_demo_resource : JWT site_id claim}
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
        $audiences = ['example.test'];
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
