<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give cosplay its own storefront section.
 *
 * The seed cosplay products (`cosplay-*-seed`) were folded into
 * `action-figures` by 2026_10_22_100800_unify_category_slugs. This creates
 * the canonical `cosplay` category (slug shared 1:1 with the storefront)
 * and moves those products into it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if (! DB::table('categories')->where('slug', 'cosplay')->exists()) {
            DB::table('categories')->insert([
                'slug' => 'cosplay',
                'name' => 'Cosplay & Phụ kiện',
                'parent_id' => null,
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $cosplayId = DB::table('categories')->where('slug', 'cosplay')->value('id');
        $actionId = DB::table('categories')->where('slug', 'action-figures')->value('id');
        if (! $cosplayId) {
            return;
        }

        $productIds = DB::table('products')->where('slug', 'like', 'cosplay-%-seed')->pluck('id')->all();
        foreach ($productIds as $productId) {
            DB::table('product_categories')->insertOrIgnore([
                'product_id' => $productId,
                'category_id' => $cosplayId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($actionId) {
                DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->where('category_id', $actionId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Keep the category: other rows may reference it by now.
    }
};
