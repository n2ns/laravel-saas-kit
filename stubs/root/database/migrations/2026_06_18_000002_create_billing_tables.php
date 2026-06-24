<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->string('pause_reason')->nullable()->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('stripe_product_id')->nullable();
            $table->string('pricing_page_url')->nullable();
            $table->string('mcp_server_url')->nullable();
            $table->string('mcp_api_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('tier', 50)->index();
            $table->string('billing_cycle', 50)->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->integer('trial_days')->default(0);
            $table->json('features')->nullable();
            $table->json('display_payload')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('plan_id')->nullable();
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('stripe_status');
            $table->index('current_period_ends_at');
            $table->index(['user_id', 'stripe_status', 'created_at'], 'subscriptions_user_status_created_idx');
            $table->index(['user_id', 'created_at'], 'subscriptions_user_created_idx');
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id');
            $table->string('stripe_id')->unique();
            $table->string('stripe_product');
            $table->string('stripe_price');
            $table->string('stripe_meter_id')->nullable();
            $table->string('stripe_meter_event_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->string('type', 50)->default('new');
            $table->string('status', 50)->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('provider_order_id')->nullable()->index();
            $table->string('provider_invoice_id')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->json('provider_data')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->json('billing_snapshot')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');
            $table->index(['status', 'paid_at'], 'orders_status_paid_idx');
            $table->index('order_number');
            $table->unique(['gateway_id', 'provider_order_id'], 'orders_provider_order_gateway_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('products');
        Schema::dropIfExists('payment_gateways');
    }
};
