<?php

namespace Database\Seeders;

use App\Models\AnimeSeries;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Dat lai ten + series + category cho san pham seed theo anh that.
 *
 * PortfolioImageSeeder dat ten chung chung ("Figure #N", series chia vong tron)
 * khong khop anh. Seeder nay (chay sau PortfolioEnrichSeeder) ghi de bang ket
 * qua nhan dien anh that. Idempotent: chay lai khong tao row moi.
 */
class SeedProductNamingSeeder extends Seeder
{
    /** slug => [name, series slug (null = khong ro), category slug, mo ta] */
    protected array $map = [
        // Figure scale / action (dir figure/)
        'figure-1-seed' => ['Son Goku Super Saiyan God - Action Figure', 'dragon-ball', 'action-figures', 'Mô hình khớp cử động Son Goku trạng thái Super Saiyan God tóc đỏ trong tư thế chiến đấu.', 'Dragon Ball'],
        'figure-2-seed' => ['Armored Titan - Titan Thiết Giáp 1/12', 'attack-on-titan', 'scale-figures', 'Mô hình Titan Thiết Giáp quỳ một chân thủ thế, chi tiết giáp và cơ bắp sắc nét.', 'Attack on Titan'],
        'figure-3-seed' => ['Gray Fullbuster Fairy Tail 1/8', 'fairy-tail', 'scale-figures', 'Mô hình Gray Fullbuster áo khoác dài tím than biểu tượng Fairy Tail, tay đút túi quần.', 'Fairy Tail'],
        'figure-4-seed' => ['Gojo Satoru - Action Figure Bịt Mắt', 'jujutsu-kaisen', 'action-figures', 'Mô hình cử động khớp Gojo Satoru tóc trắng đeo bịt mắt, trang phục đen đặc trưng.', 'Jujutsu Kaisen'],
        'figure-5-seed' => ['Nendoroid Huggy Wuggy', 'poppy-playtime', 'nendoroid', 'Nendoroid Huggy Wuggy toàn thân lông xanh, nụ cười rộng đầy răng nhọn và nơ xanh trước cổ.', 'Poppy Playtime'],
        'figure-6-seed' => ['Komi Shoko Hầu Gái Tai Mèo 1/7', 'komi-cant-communicate', 'scale-figures', 'Mô hình Komi Shoko trang phục hầu gái tai mèo, biểu cảm ngại ngùng cầm sổ ghi chép.', 'Komi Can\'t Communicate'],
        'figure-7-seed' => ['Kirito Sword Art Online 1/7', 'sword-art-online', 'scale-figures', 'Mô hình Kirito áo khoác dài đen vác kiếm trên vai.', 'Sword Art Online'],
        'figure-8-seed' => ['Cloud Strife Buster Sword 1/8', 'final-fantasy-vii', 'scale-figures', 'Mô hình Cloud Strife cầm đại kiếm Buster Sword tạo dáng chiến đấu trên đế giả đá.', 'Final Fantasy VII'],
        'figure-9-seed' => ['Aizawa Shota Eraser Head - Action Figure', 'my-hero-academia', 'action-figures', 'Mô hình khớp cử động Aizawa Shota trang phục chiến đấu đen, quàng dải khăn vải.', 'My Hero Academia'],
        'figure-10-seed' => ['Todoroki Shoto Relax Ver 1/8', 'my-hero-academia', 'scale-figures', 'Mô hình Todoroki Shoto áo phông trắng ngồi thư giãn.', 'My Hero Academia'],
        'figure-11-seed' => ['Kenshiro Hokuto no Ken 1/7', 'hokuto-no-ken', 'scale-figures', 'Mô hình Kenshiro cơ bắp áo da đen và giáp vai đứng nghiêm nghị.', 'Fist of the North Star'],
        'figure-12-seed' => ['Arima Kana Oshi no Ko 1/7', 'oshi-no-ko', 'scale-figures', 'Mô hình Arima Kana tóc đỏ đội mũ beret, đồng phục học sinh tạo dáng một chân.', 'Oshi no Ko'],
        'figure-13-seed' => ['Hatsune Miku Symphony 1/7', 'hatsune-miku', 'scale-figures', 'Mô hình Hatsune Miku tóc xanh uốn lượn, váy xếp ly và đôi cánh trắng từ nhạc cụ.', 'Hatsune Miku'],
        'figure-14-seed' => ['Hisoka Morow 1/7', 'hunter-x-hunter', 'scale-figures', 'Mô hình Hisoka tóc đỏ vuốt ngược đứng trên đế tròn trong suốt.', 'Hunter x Hunter'],
        'figure-15-seed' => ['King Harlequin - Thất Hình Đại Tội 1/8', 'seven-deadly-sins', 'scale-figures', 'Mô hình King tóc cam hoodie xanh ngọc phối cam ở tư thế lơ lửng.', 'The Seven Deadly Sins'],
        // Chibi / Nendoroid (dir chibi_figure/)
        'chibi-16-seed' => ['Nendoroid Deku U.A. Uniform', 'my-hero-academia', 'nendoroid', 'Nendoroid Izuku Midoriya tươi cười, đồng phục U.A. đeo balo.', 'My Hero Academia'],
        'chibi-17-seed' => ['Nendoroid Bakugo U.A. Uniform', 'my-hero-academia', 'nendoroid', 'Nendoroid Bakugo Katsuki đồng phục U.A. cầm chai sốt ớt.', 'My Hero Academia'],
        'chibi-18-seed' => ['Nendoroid Kobayashi', 'kobayashi-maid-dragon', 'nendoroid', 'Nendoroid Kobayashi tóc hồng đeo kính, trang phục công sở khoác túi xách.', 'Miss Kobayashi\'s Dragon Maid'],
        'chibi-19-seed' => ['Nendoroid Doll Loid Forger', 'spy-x-family', 'nendoroid', 'Búp bê Nendoroid Doll Loid Forger vest vải xanh lá kèm đế đỡ.', 'Spy x Family'],
        'chibi-20-seed' => ['Nendoroid Doll Yor Forger', 'spy-x-family', 'nendoroid', 'Nendoroid Doll Yor Forger trang phục sát thủ váy đen, vũ khí kim vàng gai nhọn.', 'Spy x Family'],
        'chibi-21-seed' => ['Nendoroid Yamada Akito Lv999', 'yamada-lv999', 'nendoroid', 'Nendoroid Akito Yamada áo be quần xám cầm điện thoại.', 'Yamada-kun to Lv999'],
        'chibi-22-seed' => ['Nendoroid Diluc', 'genshin-impact', 'nendoroid', 'Nendoroid Diluc tóc đỏ sẫm mắt đỏ, áo khoác đen sơ mi đỏ cầm hoa trắng.', 'Genshin Impact'],
        'chibi-23-seed' => ['Nendoroid Lance Crown Mashle', 'mashle', 'nendoroid', 'Nendoroid Lance Crown tóc xanh nhạt, đồng phục học viện phép thuật cầm đũa phép.', 'Mashle: Magic and Muscles'],
        'chibi-24-seed' => ['Nendoroid Mash Burnedead', 'mashle', 'nendoroid', 'Nendoroid Mash Burnedead đồng phục Easton, vạch ma thuật giả trên má.', 'Mashle: Magic and Muscles'],
        'chibi-25-seed' => ['Nendoroid Ryo Yamada Bocchi', 'bocchi-the-rock', 'nendoroid', 'Nendoroid Ryo Yamada tóc xanh ngắn cầm đàn guitar bass.', 'Bocchi the Rock!'],
        'chibi-26-seed' => ['Nendoroid Illumi Zoldyck', 'hunter-x-hunter', 'nendoroid', 'Nendoroid Illumi Zoldyck trang phục xanh lá, kim vàng đặc trưng.', 'Hunter x Hunter'],
        'chibi-27-seed' => ['Nendoroid Leorio', 'hunter-x-hunter', 'nendoroid', 'Nendoroid Leorio vest đen kính râm tròn cầm micro.', 'Hunter x Hunter'],
        'chibi-28-seed' => ['Nendoroid Sojourn Overwatch', 'overwatch', 'nendoroid', 'Nendoroid Sojourn tóc trắng cầm súng railgun đỏ trắng.', 'Overwatch 2'],
        'chibi-29-seed' => ['Nendoroid The Lamb', 'cult-of-the-lamb', 'nendoroid', 'Mô hình chibi chú cừu The Lamb đội vương miện đỏ đen đeo chuông vàng.', 'Cult of the Lamb'],
        'chibi-30-seed' => ['Yamato Look Up - One Piece', 'one-piece', 'nendoroid', 'Mô hình chibi Yamato phong cách Look Up ngồi ngước nhìn.', 'One Piece'],
        // Scene set (dir set_figure/): finished scenes -> scale-figures
        'set-figure-31-seed' => ['Luffy All Forms - Gear 5 Scene', 'one-piece', 'scale-figures', 'Mô hình tập hợp các trạng thái biến hình của Luffy tới Gear 5 trên cùng đế logo One Piece.', 'One Piece'],
        'set-figure-32-seed' => ['Joker & Morgana Persona 5', 'persona-5', 'scale-figures', 'Mô hình Joker tạo dáng nhảy cùng mèo Morgana đội vương miện.', 'Persona 5'],
        'set-figure-33-seed' => ['Black Rock Shooter Bike Ver', 'black-rock-shooter', 'scale-figures', 'Mô hình Black Rock Shooter nhào lộn trên mô tô cùng đại bác khổng lồ.', 'Black Rock Shooter'],
        'set-figure-34-seed' => ['Surtr Arknights 1/7', 'arknights', 'scale-figures', 'Mô hình Surtr tóc đỏ cầm kiếm lớn bên rào chắn công trình.', 'Arknights'],
        'set-figure-35-seed' => ['Hatsune Miku 01 Anniversary Flag', 'hatsune-miku', 'scale-figures', 'Mô hình Miku kỷ niệm cầm cờ số 01 trên đế thiên cầu la bàn vàng kim.', 'Hatsune Miku'],
        'set-figure-36-seed' => ['Hatsune Miku Magical Mirai 2023', 'hatsune-miku', 'scale-figures', 'Mô hình Miku Magical Mirai 2023 phong cách đường phố ngồi trên loa.', 'Hatsune Miku'],
        'set-figure-37-seed' => ['Tomioka Giyu Water Scene', 'demon-slayer', 'scale-figures', 'Mô hình Giyu vung kiếm cùng hiệu ứng sóng nước trong suốt.', 'Demon Slayer'],
        'set-figure-38-seed' => ['Ichigo Kurosaki Bankai 1/8', 'bleach', 'scale-figures', 'Mô hình Ichigo Bankai áo choàng đen tung bay lao về trước trên bệ đá vỡ.', 'Bleach'],
        'set-figure-39-seed' => ['Zoro Asura vs Kaido Scene', 'one-piece', 'scale-figures', 'Mô hình Zoro Cửu Kiếm Asura đối đầu rồng Kaido, hiệu ứng lửa tím.', 'One Piece'],
        'set-figure-40-seed' => ['Luffy Gear 5 vs Kaido Scene', 'one-piece', 'scale-figures', 'Mô hình tái hiện Luffy Gear 5 đối đầu Kaido dạng rồng khổng lồ.', 'One Piece'],
        'set-figure-41-seed' => ['Goku Spirit Bomb Scene', 'dragon-ball', 'scale-figures', 'Mô hình Goku giơ tay tạo Quả cầu Genki, hiệu ứng năng lượng hoành tráng.', 'Dragon Ball'],
        'set-figure-42-seed' => ['Madara Susanoo Scene', 'naruto-shippuden', 'scale-figures', 'Mô hình Madara chiến đấu cùng Susanoo xanh lam trong suốt.', 'Naruto Shippuden'],
        'set-figure-43-seed' => ['Shenron & Kid Goku Scene', 'dragon-ball', 'scale-figures', 'Mô hình rồng thần Shenron uốn lượn quanh núi mây cùng Goku nhỏ cưỡi Cân Đẩu Vân.', 'Dragon Ball'],
        'set-figure-44-seed' => ['Rengoku Flame Scene', 'demon-slayer', 'scale-figures', 'Mô hình Rengoku vung kiếm, hiệu ứng lửa xoáy sống động.', 'Demon Slayer'],
        'set-figure-45-seed' => ['Goku Kamehameha & Shenron Scene', 'dragon-ball', 'scale-figures', 'Mô hình Super Saiyan Goku thi triển Kamehameha trên đế rồng Shenron.', 'Dragon Ball'],
        // Cosplay (dir cosplay/)
        'cosplay-45-seed' => ['Mũ Bảo Hiểm Cyberpunk LED', 'original-design', 'cosplay', 'Mũ bảo hiểm phong cách cyberpunk/mecha đen có tai và đèn LED hai bên hàm.', 'Original Design'],
        'cosplay-46-seed' => ['Mặt Nạ Spider-Man Miles Morales', 'marvel', 'cosplay', 'Mặt nạ cosplay Spider-Man đen họa tiết mạng nhện, viền mắt đỏ ánh kim.', 'Marvel'],
        'cosplay-47-seed' => ['Set Cosplay Tobi Akatsuki', 'naruto-shippuden', 'cosplay', 'Set cosplay Tobi gồm áo choàng Akatsuki mây đỏ, mặt nạ xoắn ốc cam, quần và giày ninja.', 'Naruto Shippuden'],
        'cosplay-48-seed' => ['Set Cosplay Tomioka Giyu', 'demon-slayer', 'cosplay', 'Set cosplay gồm haori nửa đỏ đô nửa họa tiết, đồng phục diệt quỷ đen và thắt lưng trắng.', 'Demon Slayer'],
        'cosplay-49-seed' => ['Set Cosplay Luffy Wano', 'one-piece', 'cosplay', 'Set cosplay gồm áo khoác lửng đỏ, đai bụng vàng và quần ngố xanh viền lông trắng.', 'One Piece'],
        'cosplay-50-seed' => ['Áo Haori Tanjiro Caro', 'demon-slayer', 'cosplay', 'Áo khoác haori tay lửng họa tiết caro xanh lá đen đặc trưng của Tanjiro.', 'Demon Slayer'],
    ];

    public function run(): void
    {
        $seriesCache = [];
        $catCache = [];
        $n = 0;
        foreach ($this->map as $slug => [$name, $seriesSlug, $catSlug, $blurb, $seriesName]) {
            $p = Product::where('slug', $slug)->first();
            if (! $p) {
                continue;
            }
            $seriesId = null;
            if ($seriesSlug !== null) {
                $seriesId ??= ($seriesCache[$seriesSlug] ??= AnimeSeries::firstOrCreate(
                    ['slug' => $seriesSlug], ['name' => $seriesName, 'is_active' => true]
                )->id);
            }
            $catId = $catCache[$catSlug] ??= Category::where('slug', $catSlug)->value('id');
            $p->update([
                'name' => $name,
                'anime_series_id' => $seriesId,
                'short_description' => $name.' — ảnh thật sản phẩm',
                'description' => $name.'. '.$blurb.' Hàng chính hãng, full box, bảo hành lỗi nhà sản xuất.',
            ]);
            if ($catId) {
                $p->categories()->sync([$catId]);
            }
            $n++;
        }
        $this->command->info("renamed {$n} seed products");
    }
}
