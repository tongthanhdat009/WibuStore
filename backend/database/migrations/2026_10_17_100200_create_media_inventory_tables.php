<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('object_key', 512)->unique();
            $table->string('disk', 64)->default('s3');
            $table->string('mime_type', 128);
            $table->bigInteger('file_size_bytes')->default(0);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->string('alt_text')->nullable();
            $table->string('visibility', 16)->default('public');
            $table->string('status', 16)->default('pending');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['status', 'visibility']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->restrictOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();
            $table->unique(['media_asset_id', 'product_id', 'variant_id']);
            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('anime_series', function (Blueprint $table) {
            $table->foreign('cover_media_id')->references('id')->on('media_assets')->nullOnDelete();
        });
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->foreign('logo_media_id')->references('id')->on('media_assets')->nullOnDelete();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->timestampsTz();
            $table->unique(['warehouse_id', 'variant_id']);
        });
        DB::statement('ALTER TABLE inventory_levels ADD CONSTRAINT inventory_levels_nonneg CHECK (on_hand >= 0 AND reserved >= 0 AND reserved <= on_hand)');

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->integer('delta_on_hand')->default(0);
            $table->integer('delta_reserved')->default(0);
            $table->string('type', 64);
            $table->string('reason')->nullable();
            $table->string('reference_type', 128)->nullable();
            $table->string('reference_id', 128)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['warehouse_id', 'variant_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_levels');
        Schema::dropIfExists('warehouses');
        Schema::table('manufacturers', fn (Blueprint $t) => $t->dropForeign(['logo_media_id']));
        Schema::table('anime_series', fn (Blueprint $t) => $t->dropForeign(['cover_media_id']));
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('media_assets');
    }
};
