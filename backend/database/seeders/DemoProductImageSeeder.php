<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Gan anh that cho 3 san pham DemoSeeder (Gojo / Megumi / Nobara).
 *
 * - Gojo: dung lai anh seed co san trong bucket (figure/4 1.jpg da duoc
 *   PortfolioImageSeeder upload truoc day, khong can tai lai).
 * - Megumi / Nobara: tai anh PR chinh hang (Gundam Planet / Kotobukiya ARTFX J,
 *   anh (c) Kotobukiya, dung lam seed dev) roi upload len S3 disk 'public/...'.
 * Idempotent: chay lai khong tao row moi (firstOrCreate theo object_key / slug).
 */
class DemoProductImageSeeder extends Seeder
{
    /** slug => [alt text, s3 key co san (null = can tai), remote url (null = dung key co san)] */
    protected array $map = [
        'gojo-satoru-unlimited-void-1-7' => [
            'alt' => 'Gojo Satoru - Action Figure Bit Mat',
            'existing_key' => 'public/products/seed/figure/4 1.jpg',
            'remote_url' => null,
        ],
        'megumi-fushiguro-1-8' => [
            'alt' => 'ARTFX J Megumi Fushiguro 1/8 kem Divine Dog',
            'existing_key' => null,
            // Anh PR san pham ARTFX J Megumi (Gundam Planet, (c) Kotobukiya)
            'remote_url' => 'https://cdn.shopify.com/s/files/1/0671/4769/4277/files/1-8-artfx-j-megumi-fushiguro-fr.jpg?v=1737690435',
        ],
        'nobara-kugisaki-1-7' => [
            'alt' => 'ARTFX J Nobara Kugisaki 1/8 cam bua',
            'existing_key' => null,
            // Anh PR san pham ARTFX J Nobara (Gundam Planet, (c) Kotobukiya)
            'remote_url' => 'https://cdn.shopify.com/s/files/1/0671/4769/4277/files/1-8-artfx-j-nobara-kugisaki-fr.jpg?v=1737691579',
        ],
    ];

    public function run(): void
    {
        $disk = Storage::disk('s3');

        foreach ($this->map as $slug => $cfg) {
            $product = Product::where('slug', $slug)->first();
            if (! $product) {
                $this->command->warn("bo qua {$slug}: chua co product (chay DemoSeeder truoc)");

                continue;
            }
            try {
                $key = $cfg['existing_key'];
                if ($key !== null) {
                    if (! $disk->exists($key)) {
                        $this->command->warn("bo qua {$slug}: thieu key S3 {$key}");

                        continue;
                    }
                    $asset = MediaAsset::firstOrCreate(['object_key' => $key], [
                        'disk' => 's3', 'mime_type' => 'image/jpeg',
                        'visibility' => 'public', 'status' => 'ready',
                    ]);
                } else {
                    $res = Http::timeout(60)->get($cfg['remote_url']);
                    if (! $res->successful()) {
                        $this->command->warn("bo qua {$slug}: tai anh that bai (HTTP {$res->status()})");

                        continue;
                    }
                    $bytes = $res->body();
                    $key = "public/products/demo/{$slug}.jpg";
                    $disk->put($key, $bytes, ['ContentType' => 'image/jpeg']);
                    $size = getimagesizefromstring($bytes);
                    $asset = MediaAsset::firstOrCreate(['object_key' => $key], [
                        'disk' => 's3', 'mime_type' => 'image/jpeg',
                        'file_size_bytes' => strlen($bytes),
                        'width' => $size[0] ?? null, 'height' => $size[1] ?? null,
                        'checksum' => hash('sha256', $bytes),
                        'alt_text' => $cfg['alt'],
                        'visibility' => 'public', 'status' => 'ready',
                    ]);
                }
                ProductMedia::firstOrCreate(
                    ['product_id' => $product->id, 'variant_id' => null, 'media_asset_id' => $asset->id],
                    ['sort_order' => 0, 'is_primary' => true]
                );
                $this->command->info("anh OK: {$slug} -> {$asset->url()}");
            } catch (\Throwable $e) {
                $this->command->warn("bo qua {$slug}: {$e->getMessage()}");
            }
        }
    }
}
