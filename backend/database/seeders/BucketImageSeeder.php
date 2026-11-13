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

/**
 * Dung bucket S3 lam nguon that cho seed anh san pham.
 *
 * PortfolioImageSeeder can thu muc local (SEED_IMAGE_DIR) de upload anh;
 * khi thu muc do khong co, seed product khong duoc tao du bucket da co du
 * anh. Seeder nay doc truc tiep key 'public/products/seed/*' tren disk s3,
 * tao product/variant/inventory/media theo dung business key cua
 * PortfolioImageSeeder (slug/SKU/gia) nen hai duong hoi tu, chay lai an toan.
 * Ten that/series/mo ta do SeedProductNamingSeeder (chay sau) dat.
 * Idempotent: firstOrCreate + chi backfill metadata con thieu.
 */
class BucketImageSeeder extends Seeder
{
    /** subdir => [category slug, category name, label, base price] (giong PortfolioImageSeeder) */
    protected array $groups = [
        'figure' => ['scale-figures', 'Scale Figures', 'Figure', 3900000],
        'chibi_figure' => ['nendoroid', 'Nendoroid & Chibi', 'Chibi', 990000],
        'set_figure' => ['model-kits', 'Model Kits', 'Set Figure', 5900000],
        'cosplay' => ['cosplay', 'Cosplay & Phụ kiện', 'Cosplay', 1490000],
    ];

    public function run(): void
    {
        $disk = Storage::disk('s3');
        $warehouse = Warehouse::firstOrCreate(['code' => 'default'], ['name' => 'Kho chinh', 'is_active' => true]);

        try {
            $files = $disk->files('public/products/seed', true);
        } catch (\Throwable $e) {
            // CI dung .env.example (khong co AWS creds) hoac S3 khong toi duoc:
            // bo qua nhu PortfolioImageSeeder khi thieu SEED_IMAGE_DIR.
            $this->command->warn('khong doc duoc bucket S3 ('.$e->getMessage().') — bo qua');

            return;
        }
        if ($files === []) {
            $this->command->warn('bucket khong co key public/products/seed/* — bo qua');

            return;
        }
        $grouped = [];
        $skipped = 0;
        foreach ($files as $key) {
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $skipped++;

                continue;
            }
            if (! preg_match('#^public/products/seed/([^/]+)/(.+)\.[A-Za-z0-9]+$#', $key, $m)) {
                $skipped++;

                continue;
            }
            [$sub, $stem] = [$m[1], $m[2]];
            if (! isset($this->groups[$sub])) {
                $skipped++;

                continue;
            }
            $gid = explode(' ', $stem)[0];
            if ($gid === '') {
                $skipped++;

                continue;
            }
            $grouped[$sub][$gid][] = $key;
        }
        if ($skipped > 0) {
            $this->command->warn("bo qua {$skipped} key khong phai anh seed (ai/, dated uploads, ...)");
        }

        $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $nProducts = 0;
        $nAssets = 0;
        foreach ($this->groups as $sub => [$catSlug, $catName, $label, $basePrice]) {
            if (empty($grouped[$sub])) {
                continue;
            }
            $category = Category::firstOrCreate(['slug' => $catSlug], ['name' => $catName, 'sort_order' => 99, 'is_active' => true]);
            $gids = array_keys($grouped[$sub]);
            sort($gids, SORT_NATURAL);
            foreach ($gids as $gid) {
                $fileList = $grouped[$sub][$gid];
                sort($fileList);
                $product = Product::firstOrCreate(['slug' => Str::slug("{$label} {$gid}").'-seed'], [
                    'name' => "{$label} #{$gid}",
                    'short_description' => "{$label} #{$gid} — anh that san pham",
                    'status' => 'active', 'published_at' => now(),
                ]);
                $product->categories()->syncWithoutDetaching([$category->id]);
                $variant = ProductVariant::firstOrCreate(['sku' => sprintf('SEED-%s-%s-STD', strtoupper($sub), $gid)], [
                    'product_id' => $product->id, 'label' => 'Standard',
                    'price_amount' => $basePrice + ((int) $gid * 50000), 'status' => 'active',
                ]);
                InventoryLevel::firstOrCreate(
                    ['warehouse_id' => $warehouse->id, 'variant_id' => $variant->id],
                    ['on_hand' => 20, 'reserved' => 0]
                );
                $nProducts++;
                foreach (array_values($fileList) as $i => $key) {
                    try {
                        $asset = MediaAsset::where('object_key', $key)->first();
                        if (! $asset) {
                            $bytes = $disk->get($key);
                            $size = getimagesizefromstring($bytes);
                            $asset = MediaAsset::create([
                                'object_key' => $key, 'disk' => 's3',
                                'mime_type' => $mime[strtolower(pathinfo($key, PATHINFO_EXTENSION))] ?? 'image/jpeg',
                                'file_size_bytes' => strlen($bytes),
                                'width' => $size[0] ?? null, 'height' => $size[1] ?? null,
                                'checksum' => hash('sha256', $bytes),
                                'alt_text' => "{$label} #{$gid} — anh ".($i + 1),
                                'visibility' => 'public', 'status' => 'ready',
                            ]);
                            $nAssets++;
                        } elseif (empty($asset->file_size_bytes) || empty($asset->width)) {
                            // Backfill metadata cho row cu tao thieu thong tin (vd Gojo existing_key).
                            $bytes = $disk->get($key);
                            $size = getimagesizefromstring($bytes);
                            $asset->update([
                                'file_size_bytes' => strlen($bytes),
                                'width' => $size[0] ?? null, 'height' => $size[1] ?? null,
                                'checksum' => $asset->checksum ?: hash('sha256', $bytes),
                            ]);
                        }
                        ProductMedia::firstOrCreate(
                            ['product_id' => $product->id, 'variant_id' => null, 'media_asset_id' => $asset->id],
                            ['sort_order' => $i, 'is_primary' => $i === 0]
                        );
                    } catch (\Throwable $e) {
                        $this->command->warn("bo qua {$key}: {$e->getMessage()}");
                    }
                }
            }
            $this->command->info("seeded {$sub}: ".count($gids).' products');
        }
        $this->command->info("bucket seed xong: {$nProducts} products, {$nAssets} media_assets moi");
    }
}
