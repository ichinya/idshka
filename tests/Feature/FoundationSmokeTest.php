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
            ->assertJsonPath('service', 'IDShka')
            ->assertJsonPath('routes.health', url('/health'))
            ->assertJsonPath('routes.up', url('/up'))
            ->assertJsonMissingPath('routes.readiness');

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

    public function test_the_up_endpoint_reports_ok_as_a_stateless_json_probe(): void
    {
        $response = $this->get('/up');

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

    public function test_the_readiness_endpoint_is_forbidden_for_public_clients(): void
    {
        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
            ])
            ->get('/ready');

        $response
            ->assertForbidden()
            ->assertHeader('X-Request-Id');
    }

    public function test_the_readiness_endpoint_uses_forwarded_client_ip_when_present(): void
    {
        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '172.20.0.10',
            ])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.10',
            ])
            ->get('/ready');

        $response
            ->assertForbidden()
            ->assertHeader('X-Request-Id');
    }

    public function test_html_not_found_responses_include_csp(): void
    {
        $response = $this->get('/does-not-exist');

        $response
            ->assertNotFound()
            ->assertHeader(
                'Content-Security-Policy',
                "default-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; font-src 'self' data: https:; script-src 'self'; connect-src 'self'"
            );
    }

    public function test_unsigned_storage_error_responses_include_csp(): void
    {
        $response = $this->get('/storage/test');

        $response
            ->assertForbidden()
            ->assertHeader(
                'Content-Security-Policy',
                "default-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; font-src 'self' data: https:; script-src 'self'; connect-src 'self'"
            );
    }

    private function assertProbeResponseIsStateless(TestResponse $response): void
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        $this->assertStringStartsWith('application/json', $contentType);

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
