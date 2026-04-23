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
        Schema::create('sites', function (Blueprint $table): void {
            $table->string('id', 40)->primary();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('display_name', 120)->nullable();
            $table->string('domain', 255);
            $table->string('normalized_domain', 255);
            $table->string('verification_status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_user_id', 'normalized_domain'], 'sites_owner_domain_unique');
            $table->index('normalized_domain', 'sites_normalized_domain_idx');
            $table->index('verification_status', 'sites_verification_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
