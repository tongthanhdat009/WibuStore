<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('discount_type', 32);
            $table->bigInteger('discount_value')->default(0);
            $table->bigInteger('max_discount_amount')->nullable();
            $table->bigInteger('min_order_amount')->default(0);
            $table->timestamptz('starts_at')->nullable();
            $table->timestamptz('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('per_user_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('rules')->default('{}');
            $table->timestampsTz();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('discount_amount')->default(0);
            $table->timestampsTz();
            $table->unique(['coupon_id', 'order_id']);
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->unsignedBigInteger('current_published_revision_id')->nullable();
            $table->timestampsTz();
        });

        Schema::create('cms_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('status', 16)->default('draft');
            $table->jsonb('content')->default('{}');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamptz('published_at')->nullable();
            $table->timestampsTz();
            $table->unique(['page_id', 'version_number']);
            $table->index(['page_id', 'status']);
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->foreign('current_published_revision_id')->references('id')->on('cms_page_revisions')->nullOnDelete();
        });
        Schema::table('preorder_campaigns', function (Blueprint $table) {
            $table->foreign('terms_revision_id')->references('id')->on('cms_page_revisions')->nullOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128);
            $table->string('subject_type', 128)->nullable();
            $table->string('subject_id', 128)->nullable();
            $table->jsonb('before_redacted')->default('{}');
            $table->jsonb('after_redacted')->default('{}');
            $table->string('request_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestampsTz();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_user_id', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->jsonb('value')->default('{}');
            $table->timestampsTz();
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->string('scope', 64)->default('checkout');
            $table->jsonb('response')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamptz('expires_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::table('preorder_campaigns', fn (Blueprint $t) => $t->dropForeign(['terms_revision_id']));
        Schema::table('cms_pages', fn (Blueprint $t) => $t->dropForeign(['current_published_revision_id']));
        Schema::dropIfExists('cms_page_revisions');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
