<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_session_tokens');
        Schema::dropIfExists('user_sessions');

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('sid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 100);
            $table->string('product_code', 100);
            $table->string('device_id_hash', 64);
            $table->string('device_name')->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('revoked_reason', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_code']);
            $table->index(['user_id', 'client_id']);
            $table->index(['user_id', 'product_code', 'client_id', 'device_id_hash'], 'user_sessions_device_lookup_idx');
        });

        Schema::create('user_session_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_session_id')->constrained()->cascadeOnDelete();
            $table->string('access_token_id', 100)->unique();
            $table->string('refresh_token_id', 100)->nullable()->unique();
            $table->timestamps();

            $table->index('user_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_session_tokens');
        Schema::dropIfExists('user_sessions');

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_id', 100)->unique();
            $table->string('device_info')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }
};
