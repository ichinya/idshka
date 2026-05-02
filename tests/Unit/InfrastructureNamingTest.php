<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InfrastructureNamingTest extends TestCase
{
    public function test_demo_gateway_is_separated_from_core_compose_services(): void
    {
        $compose = file_get_contents($this->projectPath('compose.yml'));

        $this->assertIsString($compose);
        $this->assertStringContainsString('demo-resource-gateway:', $compose);
        $this->assertStringContainsString('demo-resource-api:', $compose);
        $this->assertStringContainsString('profiles: ["examples"]', $compose);
        $legacyName = $this->legacyConsumerName();

        $this->assertStringNotContainsString($legacyName.'-api:', $compose);
        $this->assertStringNotContainsString('infra/openresty/'.$legacyName, $compose);
        $this->assertStringNotContainsString('examples/'.$legacyName.'-api', $compose);
    }

    public function test_active_runtime_and_documentation_do_not_present_legacy_consumer_as_infrastructure(): void
    {
        $paths = [
            $this->projectPath('compose.yml'),
            $this->projectPath('README.md'),
            $this->projectPath('docs'),
            $this->projectPath('examples'),
            $this->projectPath('infra/openresty'),
            $this->projectPath('resources/views'),
        ];

        $hits = [];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $this->containsLegacyConsumerName($file->getPathname())) {
                        $hits[] = $this->relativePath($file->getPathname());
                    }
                }

                continue;
            }

            if ($this->containsLegacyConsumerName($path)) {
                $hits[] = $this->relativePath($path);
            }
        }

        $this->assertSame([], $hits);
    }

    private function containsLegacyConsumerName(string $path): bool
    {
        $contents = file_get_contents($path);

        return is_string($contents) && stripos($contents, $this->legacyConsumerName()) !== false;
    }

    private function legacyConsumerName(): string
    {
        return 'api'.'shka';
    }

    private function projectPath(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.$path;
    }

    private function relativePath(string $path): string
    {
        return str_replace($this->projectPath().DIRECTORY_SEPARATOR, '', $path);
    }
}
