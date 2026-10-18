<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('guest_token_hash', 128)->nullable();
            $table->string('cart_type', 16)->default('regular');
            $table->timestamptz('expires_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id']);
            $table->index(['guest_token_hash']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedBigInteger('preorder_campaign_id')->nullable();
            $table->integer('quantity');
            $table->timestampsTz();
            $table->unique(['cart_id', 'variant_id', 'preorder_campaign_id']);
        });
        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_qty_positive CHECK (quantity > 0)');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('order_number', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lookup_token_hash', 128)->nullable();
            $table->string('checkout_type', 16)->default('regular');
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone', 32);
            $table->string('order_status', 32)->default('pending');
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('fulfillment_status', 32)->default('unfulfilled');
            $table->bigInteger('subtotal_amount')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('shipping_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->char('currency', 3)->default('VND');
            $table->timestamptz('placed_at')->nullable();
            $table->timestamptz('expires_at')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'created_at']);
            $table->index(['order_status', 'created_at']);
        });
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_nonneg CHECK (subtotal_amount >= 0 AND discount_amount >= 0 AND shipping_amount >= 0 AND total_amount >= 0 AND paid_amount >= 0)');

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedBigInteger('preorder_campaign_id')->nullable();
            $table->string('sku_snapshot');
            $table->string('product_name_snapshot');
            $table->string('variant_label_snapshot')->nullable();
            $table->bigInteger('unit_price_amount');
            $table->integer('quantity');
            $table->bigInteger('line_total_amount');
            $table->timestampsTz();
            $table->index(['order_id']);
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('recipient_name');
            $table->string('phone', 32);
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('ward')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('country', 8)->default('VN');
            $table->string('postal_code', 16)->nullable();
            $table->timestampsTz();
            $table->index(['order_id', 'type']);
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('reason')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
