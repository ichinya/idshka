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
        Schema::create('oidc_redirect_uris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('oidc_client_id')->constrained('oidc_clients')->cascadeOnDelete();
            $table->string('redirect_uri', 2048);
            $table->char('redirect_uri_hash', 64);
            $table->timestamps();

            $table->unique(['oidc_client_id', 'redirect_uri_hash'], 'oidc_redirect_uris_client_hash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_redirect_uris');
    }
};
