<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_listing_shows_only_active_published(): void
    {
        $draft = Product::first();
        $draft->update(['status' => 'draft', 'published_at' => null]);

        $res = $this->getJson('/api/v1/products');
        $res->assertOk();
        $slugs = collect($res->json('data'))->pluck('slug');
        $this->assertNotContains($draft->slug, $slugs);
        $this->assertNotEmpty($slugs);
    }

    public function test_detail_and_filters(): void
    {
        $res = $this->getJson('/api/v1/products?anime_series=jujutsu-kaisen&sort=price_asc&per_page=2');
        $res->assertOk()->assertJsonCount(2, 'data');

        $slug = $res->json('data.0.slug');
        $this->getJson("/api/v1/products/{$slug}")->assertOk()->assertJsonPath('data.slug', $slug);
        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/pages/home')->assertOk()->assertJsonPath('data.sections.0.type', 'hero_comic');
    }

    /** The storefront addresses categories by the same slug the API stores. */
    public function test_category_filter_matches_the_storefront_slug(): void
    {
        $slug = $this->getJson('/api/v1/categories')->json('data.0.slug');
        $categoryId = Category::where('slug', $slug)->value('id');

        $res = $this->getJson("/api/v1/products?category={$slug}");

        $res->assertOk();
        $this->assertGreaterThan(0, $res->json('meta.total'));
        foreach ($res->json('data') as $product) {
            $this->assertTrue(
                Product::where('slug', $product['slug'])
                    ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
                    ->exists(),
                "product {$product['slug']} is not in category {$slug}",
            );
        }
    }

    public function test_availability_reflects_stock_and_preorder_campaigns(): void
    {
        $preorder = Product::where('slug', 'gojo-satoru-unlimited-void-1-7')->firstOrFail();
        $this->getJson("/api/v1/products/{$preorder->slug}")
            ->assertOk()
            ->assertJsonPath('data.availability', 'pre_order');

        $plain = Product::where('slug', 'megumi-fushiguro-1-8')->firstOrFail();
        $this->getJson("/api/v1/products/{$plain->slug}")
            ->assertOk()
            ->assertJsonPath('data.availability', 'in_stock');

        // Drain the stock: the product must stop claiming to be in stock.
        InventoryLevel::where('variant_id', $plain->variants()->value('id'))
            ->update(['on_hand' => 0, 'reserved' => 0]);
        $this->getJson("/api/v1/products/{$plain->slug}")
            ->assertOk()
            ->assertJsonPath('data.availability', 'sold_out');
    }
}
