<?php

namespace Database\Seeders;

use App\Models\AnimeSeries;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\Coupon;
use App\Models\InventoryLevel;
use App\Models\Manufacturer;
use App\Models\PreorderCampaign;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $wh = Warehouse::firstOrCreate(['code' => 'default'], ['name' => 'Kho chinh', 'is_active' => true]);

        $series = AnimeSeries::firstOrCreate(['slug' => 'jujutsu-kaisen'], ['name' => 'Jujutsu Kaisen', 'is_active' => true]);
        $mf = Manufacturer::firstOrCreate(['slug' => 'good-smile'], ['name' => 'Good Smile Company']);
        $cat = Category::firstOrCreate(['slug' => 'scale-figure'], ['name' => 'Scale Figure', 'sort_order' => 1, 'is_active' => true]);

        $figures = [
            ['name' => 'Gojo Satoru Unlimited Void 1/7', 'slug' => 'gojo-satoru-unlimited-void-1-7', 'price' => 8900000, 'sku' => 'GSC-GOJO-17-STD'],
            ['name' => 'Megumi Fushiguro 1/8', 'slug' => 'megumi-fushiguro-1-8', 'price' => 4200000, 'sku' => 'GSC-MEGUMI-18-STD'],
            ['name' => 'Nobara Kugisaki 1/7', 'slug' => 'nobara-kugisaki-1-7', 'price' => 4600000, 'sku' => 'GSC-NOBARA-17-STD'],
        ];
        foreach ($figures as $f) {
            $p = Product::firstOrCreate(['slug' => $f['slug']], [
                'name' => $f['name'], 'anime_series_id' => $series->id, 'manufacturer_id' => $mf->id,
                'short_description' => $f['name'], 'status' => 'active', 'published_at' => now(),
            ]);
            $p->categories()->syncWithoutDetaching([$cat->id]);
            $v = ProductVariant::firstOrCreate(['sku' => $f['sku']], [
                'product_id' => $p->id, 'label' => 'Standard', 'price_amount' => $f['price'], 'status' => 'active',
            ]);
            InventoryLevel::firstOrCreate(['warehouse_id' => $wh->id, 'variant_id' => $v->id], ['on_hand' => 20, 'reserved' => 0]);
        }

        $preVariant = ProductVariant::where('sku', 'GSC-GOJO-17-STD')->first();
        if ($preVariant) {
            PreorderCampaign::firstOrCreate(['variant_id' => $preVariant->id, 'status' => 'open'], [
                'quota' => 50, 'reserved_quantity' => 0, 'deposit_per_unit_amount' => 1000000,
                'sale_price_per_unit_amount' => 8900000, 'opens_at' => now()->subDay(),
                'closes_at' => now()->addDays(30), 'balance_due_days' => 14,
            ]);
        }

        Coupon::firstOrCreate(['code' => 'WIBU10'], [
            'discount_type' => 'percent', 'discount_value' => 10, 'max_discount_amount' => 500000,
            'min_order_amount' => 1000000, 'is_active' => true, 'rules' => ['allow_preorder' => false],
        ]);

        $page = CmsPage::firstOrCreate(['slug' => 'home'], ['title' => 'Trang chu']);
        $rev = CmsPageRevision::firstOrCreate(['page_id' => $page->id, 'version_number' => 1], [
            'status' => 'published', 'published_at' => now(),
            'content' => [
                'schema_version' => 1,
                'sections' => [
                    ['type' => 'hero_comic', 'enabled' => true, 'headline' => 'NEW DROP: Gojo Satoru', 'eyebrow' => 'LIMITED EDITION', 'cta_label' => 'Mua ngay', 'cta_target' => '/products/gojo-satoru-unlimited-void-1-7', 'sort_order' => 1],
                    ['type' => 'trending', 'enabled' => true, 'sort_order' => 2],
                    ['type' => 'preorder_spotlight', 'enabled' => true, 'sort_order' => 3],
                    ['type' => 'new_arrivals', 'enabled' => true, 'sort_order' => 4],
                ],
            ],
        ]);
        $page->update(['current_published_revision_id' => $rev->id]);
    }
}
