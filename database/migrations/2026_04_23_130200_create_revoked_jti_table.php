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
        Schema::create('revoked_jti', function (Blueprint $table): void {
            $table->id();
            $table->string('jti', 64);
            $table->unsignedBigInteger('api_token_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('site_id', 40);
            $table->string('audience', 255);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at');
            $table->timestamps();

            $table->foreign('api_token_id')->references('id')->on('api_tokens')->cascadeOnDelete();
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unique('jti', 'revoked_jti_jti_unique');
            $table->index(['user_id', 'site_id', 'audience'], 'revoked_jti_user_site_aud_idx');
            $table->index('expires_at', 'revoked_jti_expires_at_idx');
            $table->index('revoked_at', 'revoked_jti_revoked_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revoked_jti');
    }
};
