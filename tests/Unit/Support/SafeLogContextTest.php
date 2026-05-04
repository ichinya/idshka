<?php

namespace Tests\Unit\Support;

use App\Support\SafeLogContext;
use Illuminate\Http\Request;
use Tests\TestCase;

final class SafeLogContextTest extends TestCase
{
    public function test_it_redacts_secret_values_and_preserves_safe_identifiers(): void
    {
        $context = SafeLogContext::from([
            'client_id' => 'client_123',
            'client_secret' => 'secret_raw_value',
            'authorization_code' => 'raw_code_value',
            'code_verifier' => 'raw_verifier_value',
            'private_key_pem' => '-----BEGIN PRIVATE KEY-----',
            'raw_token' => 'raw.jwt.value',
            'error_message' => 'exception leaked secret_raw_value',
            'api_token_id' => 123,
            'jti' => 'jti-safe',
            'kid' => 'kid-safe',
        ]);

        $this->assertSame('client_123', $context['client_id']);
        $this->assertSame('[redacted]', $context['client_secret']);
        $this->assertSame('[redacted]', $context['authorization_code']);
        $this->assertSame('[redacted]', $context['code_verifier']);
        $this->assertSame('[redacted]', $context['private_key_pem']);
        $this->assertSame('[redacted]', $context['raw_token']);
        $this->assertSame('[redacted]', $context['error_message']);
        $this->assertSame(123, $context['api_token_id']);
        $this->assertSame('jti-safe', $context['jti']);
        $this->assertSame('kid-safe', $context['kid']);
    }

    public function test_it_adds_request_id_from_current_request_when_available(): void
    {
        $request = Request::create('/oauth/token', 'POST');
        $request->attributes->set('request_id', 'safe-log-request-id');

        $this->app->instance(Request::class, $request);

        $context = SafeLogContext::from([
            'client_id' => 'client_123',
        ]);

        $this->assertSame('safe-log-request-id', $context['request_id']);
        $this->assertSame('client_123', $context['client_id']);
    }
}
