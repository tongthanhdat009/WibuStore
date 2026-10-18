<?php

namespace Database\Seeders;

use App\Models\AnimeSeries;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortfolioEnrichSeeder extends Seeder
{
    protected array $series = [
        ['Jujutsu Kaisen', 'jujutsu-kaisen'],
        ['One Piece', 'one-piece'],
        ['Naruto Shippuden', 'naruto-shippuden'],
        ['Demon Slayer', 'demon-slayer'],
        ['Attack on Titan', 'attack-on-titan'],
        ['Genshin Impact', 'genshin-impact'],
        ['Hatsune Miku', 'hatsune-miku'],
        ['Spy x Family', 'spy-x-family'],
    ];

    protected array $makers = [
        ['Good Smile Company', 'good-smile-company'],
        ['Bandai Spirits', 'bandai-spirits'],
        ['Kotobukiya', 'kotobukiya'],
        ['Alter', 'alter'],
        ['MegaHouse', 'megahouse'],
        ['FuRyu', 'furyu'],
    ];

    protected array $blurb = [
        'figure' => 'Mô hình scale hoàn thiện sẵn, bám sát nguyên tác, màu sơn sắc nét.',
        'chibi_figure' => 'Chibi figure nhỏ xinh, biểu cảm đáng yêu, hợp trưng bày bàn làm việc.',
        'set_figure' => 'Set figure nhiều nhân vật trong cùng vũ trụ, mua lẻ từng mẫu hoặc full set.',
        'cosplay' => 'Phụ kiện/label cosplay liên quan, ảnh thật sản phẩm từng góc.',
    ];

    public function run(): void
    {
        $seriesIds = [];
        foreach ($this->series as [$name, $slug]) {
            $seriesIds[] = AnimeSeries::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true])->id;
        }
        $makerIds = [];
        foreach ($this->makers as [$name, $slug]) {
            $makerIds[] = Manufacturer::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
        }

        $i = 0;
        foreach (Product::where('slug', 'like', '%-seed')->orderBy('id')->get() as $p) {
            $kind = str_contains($p->slug, 'chibi') ? 'chibi_figure'
                : (str_contains($p->slug, 'set-figure') ? 'set_figure'
                : (str_contains($p->slug, 'cosplay') ? 'cosplay' : 'figure'));
            $p->update([
                'anime_series_id' => $seriesIds[$i % count($seriesIds)],
                'manufacturer_id' => $makerIds[$i % count($makerIds)],
                'short_description' => $p->name.' — '.$this->blurb[$kind],
                'description' => $p->name.'. '.$this->blurb[$kind].' Hàng chính hãng, full box, bảo hành lỗi nhà sản xuất.',
                'attributes' => array_merge($p->attributes ?? [], [
                    'line' => Str::headline(str_replace('_', ' ', $kind)),
                    'material' => 'PVC & ABS',
                    'official_license' => true,
                ]),
            ]);
            $i++;
        }
        $this->command->info("enriched {$i} seed products");
    }
}
