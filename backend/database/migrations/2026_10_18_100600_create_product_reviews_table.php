<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->smallInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('status', 16)->default('pending');
            $table->timestampsTz();
            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'status', 'created_at']);
        });
        DB::statement('ALTER TABLE product_reviews ADD CONSTRAINT product_reviews_rating CHECK (rating >= 1 AND rating <= 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
