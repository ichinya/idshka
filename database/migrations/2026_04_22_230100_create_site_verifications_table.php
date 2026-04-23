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
        Schema::create('site_verifications', function (Blueprint $table): void {
            $table->id();
            $table->string('site_id', 40);
            $table->string('method', 20);
            $table->string('token', 120);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('last_error', 120)->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->index('site_id', 'site_verifications_site_id_idx');
            $table->index(['site_id', 'status'], 'site_verifications_site_status_idx');
            $table->index('expires_at', 'site_verifications_expires_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_verifications');
    }
};
