<?php

namespace Tests\Feature;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use App\Domain\Issuer\Services\SigningKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SigningKeyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_prepare_activate_list_and_retire_keys_from_artisan(): void
    {
        $service = $this->app->make(SigningKeyService::class);
        $active = $service->createActiveKey();

        $this->artisan('idshka:keys:prepare')
            ->assertExitCode(0);

        $this->assertDatabaseHas('signing_keys', [
            'status' => SigningKeyStatus::Next->value,
        ]);

        $this->artisan('idshka:keys:activate-next')
            ->expectsOutputToContain('Activated signing key')
            ->assertExitCode(0);

        $this->artisan('idshka:keys:status')
            ->expectsOutputToContain('Signing keys')
            ->assertExitCode(0);

        $this->artisan('idshka:keys:force-retire', [
            'kid' => $active->kid,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Active->value, $active->refresh()->status);

        $this->artisan('idshka:keys:force-retire', [
            'kid' => $active->kid,
            '--force' => true,
        ])->expectsOutputToContain('Force retired signing key')
            ->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Retired->value, $active->refresh()->status);
    }

    public function test_retire_expired_command_supports_dry_run(): void
    {
        config([
            'issuer.user_api_token_ttl_seconds' => 1,
            'issuer.web_access_token_ttl_seconds' => 1,
            'issuer.id_token_ttl_seconds' => 1,
        ]);

        $service = $this->app->make(SigningKeyService::class);
        $old = $service->createActiveKey();
        $service->prepareNextKey();
        $new = $service->activateNextKey();
        $new->forceFill(['activated_at' => now()->subMinutes(10)])->save();

        $this->artisan('idshka:keys:retire-expired', [
            '--dry-run' => true,
        ])->expectsOutputToContain('Retirable signing keys: 1')
            ->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Active->value, $old->refresh()->status);

        $this->artisan('idshka:keys:retire-expired')
            ->expectsOutputToContain('Retired signing keys: 1')
            ->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Retired->value, $old->refresh()->status);
    }

    public function test_operator_can_dry_run_and_force_rollback_to_previous_active_key_from_artisan(): void
    {
        $service = $this->app->make(SigningKeyService::class);
        $first = $service->createActiveKey();
        $service->prepareNextKey();
        $second = $service->activateNextKey();

        $this->artisan('idshka:keys:rollback', [
            '--dry-run' => true,
        ])->expectsOutputToContain("Would rollback signing from {$second->kid} to {$first->kid}")
            ->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Active->value, $second->refresh()->status);

        $this->artisan('idshka:keys:rollback')
            ->expectsOutputToContain('Rollback is destructive. Re-run with --force or use --dry-run.')
            ->assertExitCode(1);

        $this->artisan('idshka:keys:rollback', [
            '--force' => true,
        ])->expectsOutputToContain("Rolled back signing key to {$first->kid}")
            ->assertExitCode(0);

        $this->assertSame(SigningKeyStatus::Retired->value, $second->refresh()->status);
        $this->assertSame($first->id, $service->requireActiveKey()->id);
    }

    public function test_key_commands_do_not_print_private_key_material_or_raw_jwts(): void
    {
        $service = $this->app->make(SigningKeyService::class);
        $active = $service->createActiveKey();
        $privatePem = $service->decryptPrivateKey($active);

        $this->artisan('idshka:keys:status')
            ->doesntExpectOutputToContain($privatePem)
            ->doesntExpectOutputToContain('BEGIN PRIVATE KEY')
            ->doesntExpectOutputToContain('eyJ')
            ->assertExitCode(0);

        $this->artisan('idshka:keys:force-retire', [
            'kid' => $active->kid,
            '--dry-run' => true,
        ])->doesntExpectOutputToContain($privatePem)
            ->doesntExpectOutputToContain('BEGIN PRIVATE KEY')
            ->doesntExpectOutputToContain('eyJ')
            ->assertExitCode(0);
    }
}
