<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unify the category slug namespace.
 *
 * The seeded categories used two vocabularies — `scale-figure` (DemoSeeder),
 * `scale-figure-seed` / `chibi-seed` / `set-figure-seed` / `cosplay-seed`
 * (PortfolioImageSeeder) — while the storefront addressed them as
 * `scale-figures` / `nendoroid` / `action-figures` / `model-kits`. The storefront
 * passed its own slug to `GET /api/v1/products?category=`, which the API matched
 * against `categories.slug`, so every category page came back empty.
 *
 * Also folds the duplicate "Scale Figure" rows into one.
 */
return new class extends Migration
{
    /** old slug => [canonical slug, display name, sort order] */
    private const MAP = [
        'scale-figure' => ['scale-figures', 'Scale Figures', 1],
        'scale-figure-seed' => ['scale-figures', 'Scale Figures', 1],
        'chibi-seed' => ['nendoroid', 'Nendoroid & Chibi', 2],
        'set-figure-seed' => ['model-kits', 'Model Kits', 4],
        'cosplay-seed' => ['action-figures', 'Action Figures', 3],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        // Create any missing canonical category before moving rows into it.
        foreach (self::MAP as [$slug, $name, $sortOrder]) {
            if (! DB::table('categories')->where('slug', $slug)->exists()) {
                DB::table('categories')->insert([
                    'slug' => $slug,
                    'name' => $name,
                    'parent_id' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (self::MAP as $from => [$to, $name, $sortOrder]) {
            if ($from === $to) {
                continue;
            }
            $fromId = DB::table('categories')->where('slug', $from)->value('id');
            $toId = DB::table('categories')->where('slug', $to)->value('id');
            if (! $fromId || ! $toId) {
                continue;
            }

            // Re-point products; the unique (product_id, category_id) index makes
            // the insert a no-op for products already linked to the target.
            $productIds = DB::table('product_categories')->where('category_id', $fromId)->pluck('product_id')->all();
            foreach ($productIds as $productId) {
                DB::table('product_categories')->insertOrIgnore([
                    'product_id' => $productId,
                    'category_id' => $toId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('product_categories')->where('category_id', $fromId)->delete();

            DB::table('categories')->where('id', $fromId)->delete();

            DB::table('categories')->where('id', $toId)->update([
                'name' => $name,
                'sort_order' => $sortOrder,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Slug renames are not reversible: the canonical rows now hold products
        // from several old categories, and splitting them back would be guesswork.
    }
};
