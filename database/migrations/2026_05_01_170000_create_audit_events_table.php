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
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('site_id', 40)->nullable();
            $table->string('category', 80);
            $table->string('action', 120);
            $table->string('summary', 255);
            $table->json('metadata');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->index(['user_id', 'occurred_at'], 'audit_events_user_occurred_idx');
            $table->index(['site_id', 'occurred_at'], 'audit_events_site_occurred_idx');
            $table->index(['category', 'action'], 'audit_events_category_action_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
