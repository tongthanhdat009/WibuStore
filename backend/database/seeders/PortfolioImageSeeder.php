<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioImageSeeder extends Seeder
{
    /** subdir => [category slug/name, product label, base price] */
    protected array $groups = [
        'figure' => ['scale-figures', 'Scale Figures', 'Figure', 3900000],
        'chibi_figure' => ['nendoroid', 'Nendoroid & Chibi', 'Chibi', 990000],
        'set_figure' => ['model-kits', 'Model Kits', 'Set Figure', 5900000],
        'cosplay' => ['cosplay', 'Cosplay & Phụ kiện', 'Cosplay', 1490000],
    ];

    public function run(): void
    {
        $base = rtrim((string) env('SEED_IMAGE_DIR', database_path('seeders/data/images')), '/');
        if (! is_dir($base)) {
            $this->command->warn("SEED_IMAGE_DIR missing: {$base} (mount wibu-images RO). Skip.");

            return;
        }
        $disk = Storage::disk('s3');
        $warehouse = Warehouse::firstOrCreate(['code' => 'default'], ['name' => 'Kho chinh', 'is_active' => true]);

        foreach ($this->groups as $sub => [$catSlug, $catName, $label, $basePrice]) {
            $dir = $base.'/'.$sub;
            if (! is_dir($dir)) {
                continue;
            }
            $category = Category::firstOrCreate(['slug' => $catSlug], ['name' => $catName, 'sort_order' => 99, 'is_active' => true]);
            $grouped = [];
            foreach (glob($dir.'/*.jpg') ?: [] as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME); // "10 1"
                [$gid] = explode(' ', $name) + [null];
                $grouped[$gid][] = $file;
            }
            ksort($grouped, SORT_NATURAL);
            foreach ($grouped as $gid => $files) {
                sort($files);
                $sku = sprintf('SEED-%s-%s-STD', strtoupper($sub), $gid);
                $product = Product::firstOrCreate(['slug' => Str::slug("{$label} {$gid}").'-seed'], [
                    'name' => "{$label} #{$gid}",
                    'short_description' => "{$label} #{$gid} — anh that san pham",
                    'status' => 'active', 'published_at' => now(),
                ]);
                $product->categories()->syncWithoutDetaching([$category->id]);
                $variant = ProductVariant::firstOrCreate(['sku' => $sku], [
                    'product_id' => $product->id, 'label' => 'Standard',
                    'price_amount' => $basePrice + ((int) $gid * 50000), 'status' => 'active',
                ]);
                InventoryLevel::firstOrCreate(
                    ['warehouse_id' => $warehouse->id, 'variant_id' => $variant->id],
                    ['on_hand' => 20, 'reserved' => 0]
                );
                foreach (array_values($files) as $i => $file) {
                    $key = "public/products/seed/{$sub}/".basename($file);
                    if (! $disk->exists($key)) {
                        $disk->put($key, file_get_contents($file), ['ContentType' => 'image/jpeg']);
                    }
                    $asset = MediaAsset::firstOrCreate(['object_key' => $key], [
                        'disk' => 's3', 'mime_type' => 'image/jpeg',
                        'file_size_bytes' => filesize($file),
                        'width' => getimagesize($file)[0] ?? null,
                        'height' => getimagesize($file)[1] ?? null,
                        'checksum' => hash_file('sha256', $file),
                        'visibility' => 'public', 'status' => 'ready',
                    ]);
                    ProductMedia::firstOrCreate(
                        ['product_id' => $product->id, 'variant_id' => null, 'media_asset_id' => $asset->id],
                        ['sort_order' => $i, 'is_primary' => $i === 0]
                    );
                }
            }
            $this->command->info("seeded {$sub}: ".count($grouped).' products');
        }
    }
}
