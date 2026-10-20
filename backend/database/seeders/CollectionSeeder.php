<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    protected array $collections = [
        [
            'slug' => 'collection-shibuya-incident',
            'title' => 'Arc Biến Cố Shibuya',
            'tagline' => 'Những figure tái hiện trận chiến khốc liệt nhất Jujutsu Kaisen',
            'description' => 'Tuyển chọn các mô hình Gojo, Megumi, Nobara phiên bản Shibuya Incident chính hãng.',
            'badge' => 'HOT ARCHIVE',
            'products' => ['gojo-satoru-unlimited-void-1-7', 'megumi-fushiguro-1-8', 'nobara-kugisaki-1-7'],
        ],
        [
            'slug' => 'collection-chibi-world',
            'title' => 'Thế Giới Chibi',
            'tagline' => 'Chibi figure nhỏ xinh, giá mềm, hợp trưng bày mọi góc',
            'description' => 'Bộ sưu tập chibi Nendoroid-style đáng yêu cho mọi kệ trưng bày.',
            'badge' => 'CUTE ZONE',
            'products' => ['chibi-16-seed', 'chibi-17-seed', 'chibi-18-seed', 'chibi-19-seed', 'chibi-20-seed', 'chibi-21-seed', 'chibi-22-seed', 'chibi-23-seed'],
        ],
        [
            'slug' => 'collection-cosplay-zone',
            'title' => 'Cosplay Zone',
            'tagline' => 'Trang phục và phụ kiện cosplay chuẩn nguyên tác',
            'description' => 'Đồ cosplay từ Naruto, One Piece, Jujutsu Kaisen đến Spy x Family.',
            'badge' => 'COSPLAY',
            'products' => ['cosplay-45-seed', 'cosplay-46-seed', 'cosplay-47-seed', 'cosplay-48-seed', 'cosplay-49-seed', 'cosplay-50-seed'],
        ],
        [
            'slug' => 'collection-scale-showcase',
            'title' => 'Scale Showcase',
            'tagline' => 'Scale figure tỉ lệ chuẩn cho collector khó tính',
            'description' => 'Các mẫu scale 1/7 hoàn thiện cao, màu sơn sắc nét.',
            'badge' => 'PREMIUM',
            'products' => ['figure-1-seed', 'figure-2-seed', 'figure-3-seed', 'figure-4-seed', 'figure-5-seed', 'figure-6-seed', 'figure-7-seed', 'figure-8-seed'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->collections as $c) {
            $page = CmsPage::firstOrCreate(['slug' => $c['slug']], ['title' => $c['title']]);
            $page->update(['title' => $c['title']]);
            $version = ($page->revisions()->max('version_number') ?? 0) + 1;
            $rev = CmsPageRevision::create([
                'page_id' => $page->id,
                'version_number' => $version,
                'status' => 'published',
                'content' => [
                    'title' => $c['title'],
                    'tagline' => $c['tagline'],
                    'description' => $c['description'],
                    'badge' => $c['badge'],
                    'products' => $c['products'],
                ],
                'published_at' => now(),
            ]);
            $page->update(['current_published_revision_id' => $rev->id]);
        }
        $this->command->info('seeded '.count($this->collections).' collections');
    }
}
