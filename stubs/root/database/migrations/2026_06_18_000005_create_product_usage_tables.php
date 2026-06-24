<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $clients = ['starter'];

    public function up(): void
    {
        foreach ($this->clients as $client) {
            Schema::create("product_usage_events_{$client}", function (Blueprint $table) use ($client) {
                $table->id();
                $table->uuid('event_id')->nullable()->unique();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('client', 50)->default($client);
                $table->string('event');
                $table->string('event_type', 50)->nullable();
                $table->string('category', 50)->nullable();
                $table->string('locale', 10)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('role', 50)->nullable();
                $table->string('prompt_key', 100)->nullable();
                $table->string('prompt_label', 100)->nullable();
                $table->integer('tokens_in')->default(0);
                $table->integer('tokens_out')->default(0);
                $table->json('properties')->nullable();
                $table->json('context')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event', 'created_at']);
                $table->index('event');
                $table->index('created_at');
            });

            Schema::create("product_usage_daily_{$client}", function (Blueprint $table) use ($client) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->unsignedInteger('event_count')->default(0);
                $table->unsignedBigInteger('tokens_in_total')->default(0);
                $table->unsignedBigInteger('tokens_out_total')->default(0);
                $table->json('event_breakdown')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date'], "idx_{$client}_daily_user_date");
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->clients) as $client) {
            Schema::dropIfExists("product_usage_daily_{$client}");
            Schema::dropIfExists("product_usage_events_{$client}");
        }
    }
};
