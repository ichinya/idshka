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
        Schema::create('oauth_authorization_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('oidc_client_id')->constrained('oidc_clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('site_id', 40);
            $table->char('code_hash', 64);
            $table->string('redirect_uri', 2048);
            $table->json('scopes');
            $table->string('nonce', 255);
            $table->string('code_challenge', 255);
            $table->string('code_challenge_method', 16);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unique('code_hash', 'oauth_authorization_codes_hash_unique');
            $table->index(['oidc_client_id', 'user_id'], 'oauth_authorization_codes_client_user_idx');
            $table->index('expires_at', 'oauth_authorization_codes_expires_at_idx');
            $table->index('consumed_at', 'oauth_authorization_codes_consumed_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_authorization_codes');
    }
};
