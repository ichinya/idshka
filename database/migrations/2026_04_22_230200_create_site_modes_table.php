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
        Schema::create('site_modes', function (Blueprint $table): void {
            $table->id();
            $table->string('site_id', 40);
            $table->string('mode', 20);
            $table->timestamp('enabled_at');
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unique(['site_id', 'mode'], 'site_modes_site_mode_unique');
            $table->index(['site_id', 'mode'], 'site_modes_site_id_mode_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_modes');
    }
};
