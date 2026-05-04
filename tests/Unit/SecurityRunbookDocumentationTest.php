<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SecurityRunbookDocumentationTest extends TestCase
{
    public function test_operations_runbook_covers_backup_restore_scope_without_secret_dumps(): void
    {
        $contents = $this->readDoc('docs/OPERATIONS.md');

        foreach ([
            'PostgreSQL',
            'Redis revoke denylist',
            'storage',
            'runtime config',
            'APP_KEY',
            'signing keys',
            'users',
            'sites',
            'modes',
            'token metadata',
            'OIDC clients',
            'redirect URIs',
            'authorization codes',
            'audit events',
            'JWKS cache rebuild',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }

        $this->assertStringContainsString('restore drill evidence', strtolower($contents));
        $this->assertStringContainsString('without dumping raw secrets', strtolower($contents));
    }

    public function test_security_runbook_covers_required_incidents_and_safe_identifiers(): void
    {
        $contents = $this->readDoc('docs/SECURITY_RUNBOOK.md');

        foreach ([
            'Leaked API token',
            'Leaked client secret',
            'Leaked authorization code',
            'Leaked Socialite provider token',
            'Leaked signing key',
            'Compromised APP_KEY',
            'Gateway header trust failure',
            'jti',
            'client_id',
            'kid',
            'site_id',
            'request_id',
            'revoke and recreate',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }

        $this->assertStringNotContainsString('<raw-token>', $contents);
        $this->assertStringNotContainsString('<client-secret>', $contents);
    }

    public function test_docs_index_and_flow_docs_link_to_security_runbooks(): void
    {
        $docsIndex = $this->readDoc('docs/README.md');
        $apiFlows = $this->readDoc('docs/API_FLOWS.md');
        $gatewayContract = $this->readDoc('docs/GATEWAY_CONTRACT.md');

        foreach ([
            'OPERATIONS.md',
            'SECURITY_RUNBOOK.md',
        ] as $link) {
            $this->assertStringContainsString($link, $docsIndex);
        }

        $this->assertStringContainsString('SECURITY_RUNBOOK.md', $apiFlows);
        $this->assertStringContainsString('SECURITY_RUNBOOK.md', $gatewayContract);
    }

    private function readDoc(string $path): string
    {
        $absolutePath = $this->projectPath($path);

        $this->assertFileExists($absolutePath);

        $contents = file_get_contents($absolutePath);
        $this->assertIsString($contents);

        return $contents;
    }

    private function projectPath(string $path): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
