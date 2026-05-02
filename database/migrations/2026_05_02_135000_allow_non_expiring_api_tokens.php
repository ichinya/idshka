<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setTimestampNullable('api_tokens', 'expires_at', true);
        $this->setTimestampNullable('revoked_jti', 'expires_at', true);
    }

    public function down(): void
    {
        DB::table('revoked_jti')->whereNull('expires_at')->update(['expires_at' => now()]);
        DB::table('api_tokens')->whereNull('expires_at')->update(['expires_at' => now()]);

        $this->setTimestampNullable('revoked_jti', 'expires_at', false);
        $this->setTimestampNullable('api_tokens', 'expires_at', false);
    }

    private function setTimestampNullable(string $table, string $column, bool $nullable): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            $nullability = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} {$nullability}");

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $nullability = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE {$table} MODIFY {$column} TIMESTAMP {$nullability}");
        }
    }
};
