<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CiWorkflowSecurityTest extends TestCase
{
    public function test_ci_workflow_contains_security_gates_and_hardening_tests(): void
    {
        $workflow = $this->readFile('.github/workflows/ci.yml');

        $this->assertStringContainsString('permissions:', $workflow);
        $this->assertStringContainsString('contents: read', $workflow);
        $this->assertStringContainsString('concurrency:', $workflow);
        $this->assertStringContainsString('composer audit', $workflow);
        $this->assertStringContainsString('npm audit --audit-level=high', $workflow);
        $this->assertStringContainsString('php artisan config:cache', $workflow);
        $this->assertStringContainsString('php artisan route:list', $workflow);

        foreach ([
            'tests/Feature/SecurityRateLimitTest.php',
            'tests/Feature/SigningKeyCommandTest.php',
            'tests/Unit/SecurityRunbookDocumentationTest.php',
            'tests/Unit/GatewayLoggingSafetyTest.php',
        ] as $hardeningSuite) {
            $this->assertStringContainsString($hardeningSuite, $workflow);
        }
    }

    public function test_gateway_smoke_covers_unknown_kid_and_jwks_unavailable_fail_closed(): void
    {
        $smoke = $this->readFile('infra/openresty/demo-resource/smoke.sh');

        $this->assertStringContainsString('checking unknown kid', $smoke);
        $this->assertStringContainsString('unknown-kid', $smoke);
        $this->assertStringContainsString('"JWT signing key is unknown."', $smoke);
        $this->assertStringContainsString('checking JWKS cache TTL expiry', $smoke);
        $this->assertStringContainsString('rotate_and_retire_kid "$valid_kid"', $smoke);
        $this->assertStringContainsString('forceRetireByKid($argv[1])', $smoke);
        $this->assertStringContainsString('cached key before ttl expiry', $smoke);
        $this->assertStringContainsString('stale key after ttl expiry', $smoke);
        $this->assertStringContainsString('resolve_gateway_jwks_cache_seconds()', $smoke);
        $this->assertStringContainsString('GATEWAY_SERVICE="${GATEWAY_SERVICE:-demo-resource-gateway}"', $smoke);
        $this->assertStringContainsString('$COMPOSE exec -T "$GATEWAY_SERVICE" printenv GATEWAY_JWKS_CACHE_SECONDS', $smoke);
        $this->assertStringContainsString('[FIX:gateway-smoke-cache-ttl]', $smoke);
        $this->assertStringContainsString('checking JWKS unavailable fail-closed behavior', $smoke);
        $this->assertStringContainsString('jwks_unavailable', $smoke);
        $this->assertStringContainsString('$COMPOSE stop nginx', $smoke);
    }

    private function readFile(string $path): string
    {
        $absolutePath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        $contents = file_get_contents($absolutePath);

        $this->assertIsString($contents);

        return $contents;
    }
}
