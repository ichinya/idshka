<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NonExpiringApiTokenMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_upgrade_makes_legacy_token_expiration_columns_nullable(): void
    {
        Schema::dropIfExists('revoked_jti');
        Schema::dropIfExists('api_tokens');

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('expires_at');
        });

        Schema::create('revoked_jti', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('expires_at');
        });

        $migration = require database_path('migrations/2026_05_02_135000_allow_non_expiring_api_tokens.php');

        $migration->up();

        DB::table('api_tokens')->insert(['expires_at' => null]);
        DB::table('revoked_jti')->insert(['expires_at' => null]);

        $this->assertDatabaseHas('api_tokens', ['expires_at' => null]);
        $this->assertDatabaseHas('revoked_jti', ['expires_at' => null]);
    }
}
