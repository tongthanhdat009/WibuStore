<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime_series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['is_active', 'name']);
        });

        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website_url', 512)->nullable();
            $table->unsignedBigInteger('logo_media_id')->nullable();
            $table->timestampsTz();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('anime_series_id')->nullable()->constrained('anime_series')->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('attributes')->default('{}');
            $table->string('status', 32)->default('draft');
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamptz('published_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['status', 'published_at']);
            $table->index(['anime_series_id']);
            $table->index(['manufacturer_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('label');
            $table->jsonb('attributes')->default('{}');
            $table->bigInteger('price_amount');
            $table->bigInteger('compare_at_amount')->nullable();
            $table->integer('weight_grams')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->string('status', 32)->default('active');
            $table->timestampsTz();
            $table->index(['product_id', 'status']);
        });
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_price_nonneg CHECK (price_amount >= 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_compare CHECK (compare_at_amount IS NULL OR compare_at_amount >= price_amount)');

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->unique(['product_id', 'category_id']);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('anime_series');
    }
};
