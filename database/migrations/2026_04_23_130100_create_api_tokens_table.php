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
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('site_id', 40);
            $table->unsignedBigInteger('signing_key_id');
            $table->string('audience', 255);
            $table->string('jti', 64);
            $table->char('token_hash', 64);
            $table->json('scopes');
            $table->json('permissions');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->foreign('signing_key_id')->references('id')->on('signing_keys')->cascadeOnDelete();
            $table->unique('jti', 'api_tokens_jti_unique');
            $table->unique('token_hash', 'api_tokens_token_hash_unique');
            $table->index(['user_id', 'site_id', 'audience'], 'api_tokens_user_site_aud_idx');
            $table->index('expires_at', 'api_tokens_expires_at_idx');
            $table->index('revoked_at', 'api_tokens_revoked_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
