<?php

namespace Tests\Feature;

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
}
