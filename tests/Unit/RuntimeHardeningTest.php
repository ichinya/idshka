<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RuntimeHardeningTest extends TestCase
{
    public function test_php_entrypoint_can_run_migrations_before_starting_fpm_as_non_root(): void
    {
        $entrypoint = file_get_contents($this->projectPath('infra/docker/php/entrypoint.sh'));

        $this->assertIsString($entrypoint);
        $this->assertStringNotContainsString('IDSHKA_AUTORUN_MIGRATIONS', $entrypoint);
        $this->assertStringNotContainsString('is_truthy()', $entrypoint);
        $this->assertStringContainsString('[FIX:runtime-hardening] running database migrations', $entrypoint);
        $this->assertStringContainsString('php artisan migrate --force', $entrypoint);
        $this->assertStringContainsString('su-exec www-data "$@"', $entrypoint);
    }

    public function test_compose_waits_for_migrated_app_before_starting_public_nginx(): void
    {
        $compose = file_get_contents($this->projectPath('compose.yml'));

        $this->assertIsString($compose);
        $this->assertStringNotContainsString('IDSHKA_AUTORUN_MIGRATIONS', $compose);
        $this->assertStringContainsString('php artisan migrate:status --no-interaction', $compose);
        $this->assertStringContainsString('timeout: 30s', $compose);
        $this->assertStringContainsString('start_period: 60s', $compose);
        $this->assertMatchesRegularExpression(
            '/nginx:\R(?:.*\R){1,8}\s+depends_on:\R\s+app:\R\s+condition: service_healthy/',
            $compose
        );
    }

    public function test_php_image_uses_su_exec_and_suppresses_rootless_fpm_pool_user_notices(): void
    {
        $dockerfile = file_get_contents($this->projectPath('infra/docker/php/Dockerfile'));

        $this->assertIsString($dockerfile);
        $this->assertStringContainsString('su-exec', $dockerfile);
        $this->assertStringContainsString("s/^(user|group) = /; \\0/", $dockerfile);
    }

    private function projectPath(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.$path;
    }
}
