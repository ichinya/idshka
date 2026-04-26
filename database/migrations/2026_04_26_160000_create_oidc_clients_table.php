<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oidc_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('site_id', 40);
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('client_id', 80);
            $table->string('client_secret_hash', 255);
            $table->string('name', 120);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unique('client_id', 'oidc_clients_client_id_unique');
            $table->index(['owner_user_id', 'site_id'], 'oidc_clients_owner_site_idx');
            $table->index('revoked_at', 'oidc_clients_revoked_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_clients');
    }
};
