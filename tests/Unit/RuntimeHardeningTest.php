<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RuntimeHardeningTest extends TestCase
{
    public function test_php_entrypoint_runs_migrations_and_does_not_force_production_migrations(): void
    {
        $entrypoint = file_get_contents($this->projectPath('infra/docker/php/entrypoint.sh'));

        $this->assertIsString($entrypoint);
        $this->assertStringNotContainsString('IDSHKA_AUTORUN_MIGRATIONS', $entrypoint);
        $this->assertStringNotContainsString('is_truthy()', $entrypoint);
        $this->assertStringContainsString('app_env="${APP_ENV:-local}"', $entrypoint);
        $this->assertStringNotContainsString('skipping automatic database migrations', $entrypoint);
        $this->assertStringContainsString('running database migrations for production runtime without --force', $entrypoint);
        $this->assertStringContainsString('running database migrations for $app_env runtime', $entrypoint);
        $this->assertMatchesRegularExpression(
            '/if \[ "\$app_env" = "production" \]; then\R\s+echo .*\R\s+run_as_app_user php artisan migrate\Relse/',
            $entrypoint
        );
        $this->assertStringContainsString('php artisan migrate --no-interaction', $entrypoint);
        $this->assertStringNotContainsString('php artisan migrate --force', $entrypoint);
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

    public function test_local_docker_runtime_avoids_hot_path_logs_on_bind_mount(): void
    {
        $fpmConfig = file_get_contents($this->projectPath('infra/docker/php/fpm-non-root.conf'));
        $envExample = file_get_contents($this->projectPath('.env.example'));

        $this->assertIsString($fpmConfig);
        $this->assertStringNotContainsString('/var/www/html/storage/logs', $fpmConfig);
        $this->assertStringContainsString('error_log = /tmp/php-fpm-error.log', $fpmConfig);
        $this->assertStringContainsString('access.log = /tmp/php-fpm-access.log', $fpmConfig);

        $this->assertIsString($envExample);
        $this->assertStringContainsString('LOG_STACK=stderr', $envExample);
        $this->assertStringContainsString('LOG_LEVEL=info', $envExample);
        $this->assertStringNotContainsString('LOG_STACK=single,stderr', $envExample);
    }

    private function projectPath(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.$path;
    }
}
