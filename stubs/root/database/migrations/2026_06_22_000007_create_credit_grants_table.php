<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('product_code', 100)->index();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('used')->default(0);
            $table->string('source_type', 50)->nullable();
            $table->string('source_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_code', 'expires_at'], 'credit_grants_user_product_expiry_idx');
            $table->unique(['source_type', 'source_id', 'product_code'], 'credit_grants_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_grants');
    }
};
