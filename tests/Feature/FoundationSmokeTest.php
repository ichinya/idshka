<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FoundationSmokeTest extends TestCase
{
    public function test_the_root_endpoint_reports_foundation_metadata(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'foundation-ready')
            ->assertJsonPath('service', 'IDShka');

        $this->assertProbeResponseIsStateless($response);
    }

    public function test_the_health_endpoint_reports_ok(): void
    {
        $response = $this->get('/health');

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'IDShka');

        $this->assertProbeResponseIsStateless($response);
    }

    public function test_the_readiness_endpoint_reports_ok_for_the_testing_environment(): void
    {
        $response = $this->get('/ready');

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok');

        $this->assertProbeResponseIsStateless($response);
        $this->assertArrayNotHasKey('connection', $response->json('checks.database'));
        $this->assertArrayNotHasKey('response', $response->json('checks.redis'));
        $this->assertArrayNotHasKey('reason', $response->json('checks.redis'));
    }

    private function assertProbeResponseIsStateless(TestResponse $response): void
    {
        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'")
            ->assertHeaderMissing('Set-Cookie');

        $cacheControl = (string) $response->headers->get('Cache-Control', '');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }
}
