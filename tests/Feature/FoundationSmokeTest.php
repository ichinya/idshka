<?php

namespace Tests\Feature;

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
    }

    public function test_the_health_endpoint_reports_ok(): void
    {
        $response = $this->get('/health');

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'IDShka');
    }

    public function test_the_readiness_endpoint_reports_ok_for_the_testing_environment(): void
    {
        $response = $this->get('/ready');

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok');
    }
}
