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
        Schema::create('signing_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('kid', 64);
            $table->string('algorithm', 20);
            $table->json('public_jwk');
            $table->text('public_key_pem');
            $table->longText('private_key_encrypted');
            $table->string('status', 20);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique('kid', 'signing_keys_kid_unique');
            $table->index(['status', 'activated_at'], 'signing_keys_status_activated_idx');
            $table->index('retired_at', 'signing_keys_retired_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signing_keys');
    }
};
