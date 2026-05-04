<?php

namespace Tests\Unit;

use Tests\TestCase;

final class GatewayLoggingSafetyTest extends TestCase
{
    public function test_jwks_cache_logs_include_request_id_context(): void
    {
        $jwksCache = file_get_contents(base_path('infra/openresty/demo-resource/lua/jwks_cache.lua'));
        $jwtValidate = file_get_contents(base_path('infra/openresty/demo-resource/lua/jwt_validate.lua'));

        $this->assertIsString($jwksCache);
        $this->assertIsString($jwtValidate);
        $this->assertStringContainsString('function _M.get_key(kid, ttl_seconds, request_id)', $jwksCache);
        $this->assertStringContainsString('local keys, error_response = fetch_jwks(request_id, kid)', $jwksCache);
        $this->assertStringContainsString('jwks_cache.get_key(header.kid, options.jwks_cache_seconds, request_id)', $jwtValidate);
        $this->assertMatchesRegularExpression('/log_(?:debug|warn)\\("[^"]+", \\{ request_id = request_id, kid = kid/', $jwksCache);
    }

    public function test_jwks_cache_has_explicit_ttl_and_stale_key_policy(): void
    {
        $jwksCache = file_get_contents(base_path('infra/openresty/demo-resource/lua/jwks_cache.lua'));
        $nginx = file_get_contents(base_path('infra/openresty/demo-resource/nginx.conf'));

        $this->assertIsString($jwksCache);
        $this->assertIsString($nginx);
        $this->assertStringContainsString('expires_at', $jwksCache);
        $this->assertStringContainsString('cache_expired', $jwksCache);
        $this->assertStringContainsString('cache:delete(cache_key)', $jwksCache);
        $this->assertStringContainsString('cache_miss', $jwksCache);
        $this->assertStringContainsString('GATEWAY_JWKS_CACHE_SECONDS', $nginx);
        $this->assertStringContainsString('os.getenv("GATEWAY_JWKS_CACHE_SECONDS")', $nginx);
    }

    public function test_gateway_smoke_failures_redact_bodies_before_printing(): void
    {
        $smoke = file_get_contents(base_path('infra/openresty/demo-resource/smoke.sh'));

        $this->assertIsString($smoke);
        $this->assertStringContainsString('sanitize_body_for_logs()', $smoke);
        $this->assertStringContainsString('sanitize_body_for_logs >&2', $smoke);
        $this->assertStringNotContainsString('echo "$body" >&2', $smoke);
    }
}
