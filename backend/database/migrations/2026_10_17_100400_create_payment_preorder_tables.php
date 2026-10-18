<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('kind', 16)->default('full');
            $table->string('method', 32)->default('cod');
            $table->string('provider', 64)->nullable();
            $table->string('provider_reference', 256)->nullable();
            $table->bigInteger('amount');
            $table->string('status', 16)->default('pending');
            $table->timestamptz('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->text('reject_reason')->nullable();
            $table->timestampsTz();
            $table->index(['order_id', 'status']);
        });
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_nonneg CHECK (amount >= 0)');
        DB::statement('CREATE UNIQUE INDEX payments_provider_ref_uidx ON payments (provider, provider_reference) WHERE provider IS NOT NULL AND provider_reference IS NOT NULL');

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 64);
            $table->string('event_id', 256);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->jsonb('payload_redacted')->default('{}');
            $table->string('processing_status', 32)->default('pending');
            $table->timestampsTz();
            $table->unique(['provider', 'event_id']);
        });

        Schema::create('preorder_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('status', 16)->default('draft');
            $table->timestamptz('opens_at')->nullable();
            $table->timestamptz('closes_at')->nullable();
            $table->timestamptz('expected_release_at')->nullable();
            $table->timestamptz('expected_arrival_at')->nullable();
            $table->integer('quota')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->bigInteger('deposit_per_unit_amount')->default(0);
            $table->bigInteger('sale_price_per_unit_amount')->default(0);
            $table->integer('balance_due_days')->default(14);
            $table->unsignedBigInteger('terms_revision_id')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'opens_at', 'closes_at']);
        });
        DB::statement('ALTER TABLE preorder_campaigns ADD CONSTRAINT preorder_quota CHECK (quota >= 0 AND reserved_quantity >= 0 AND reserved_quantity <= quota)');
        DB::statement('ALTER TABLE preorder_campaigns ADD CONSTRAINT preorder_deposit CHECK (deposit_per_unit_amount >= 0 AND sale_price_per_unit_amount >= 0 AND deposit_per_unit_amount <= sale_price_per_unit_amount)');

        Schema::create('preorder_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('preorder_campaigns')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('status', 32)->default('awaiting_deposit');
            $table->bigInteger('deposit_required_amount')->default(0);
            $table->bigInteger('balance_due_amount')->default(0);
            $table->timestamptz('deposit_deadline_at')->nullable();
            $table->timestamptz('balance_deadline_at')->nullable();
            $table->timestamptz('deposit_paid_at')->nullable();
            $table->timestampsTz();
            $table->index(['campaign_id', 'status']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreign('preorder_campaign_id')->references('id')->on('preorder_campaigns')->nullOnDelete();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('preorder_campaign_id')->references('id')->on('preorder_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropForeign(['preorder_campaign_id']));
        Schema::table('cart_items', fn (Blueprint $t) => $t->dropForeign(['preorder_campaign_id']));
        Schema::dropIfExists('preorder_reservations');
        Schema::dropIfExists('preorder_campaigns');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
