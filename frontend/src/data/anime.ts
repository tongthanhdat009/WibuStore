import type { AnimeSeries } from '../types';

// Ảnh bìa series = logo anime chính thức (Wikimedia Commons, raster 960px,
// verify 200) thay cho ảnh Unsplash chung chung trước đây.
// logoOnDark = true: logo sáng màu (trắng/xám) → component tự đặt lên nền tối.
// NOTE: Chainsaw Man và Evangelion đã bỏ khỏi danh sách vì DB chưa có
// sản phẩm nào thuộc 2 series này (trang /anime/... trống trơn).
// Khi nào có hàng thì thêm entry + logo vào đây và LOGO là xong.
const LOGO = {
  jjk: 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/52/Jujutsu_Kaisen_logo.svg/960px-Jujutsu_Kaisen_logo.svg.png',
  onePiece: 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6c/One_piece_logo.svg/960px-One_piece_logo.svg.png',
  demonSlayer: 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/fc/Demon_Slayer_logo.svg/960px-Demon_Slayer_logo.svg.png',
  spyFamily: 'https://upload.wikimedia.org/wikipedia/commons/3/31/Spy_%C3%97_Family_logo.png',
};

export const animeSeriesList: AnimeSeries[] = [
  {
    id: 'jjk',
    slug: 'jujutsu-kaisen',
    title: 'Jujutsu Kaisen',
    japaneseTitle: '呪術廻戦',
    characterCount: 18,
    bannerImage: LOGO.jjk,
    coverImage: LOGO.jjk,
    description: 'Thế giới nguyền hồn và những trận chiến định mệnh của các Chú Thuật Sư trường Tokyo.',
    figureCount: 28,
    themeColor: '#3258D7',
  },
  {
    id: 'one-piece',
    slug: 'one-piece',
    title: 'One Piece',
    japaneseTitle: 'ワンピース',
    characterCount: 35,
    bannerImage: LOGO.onePiece,
    coverImage: LOGO.onePiece,
    description: 'Hành trình vượt Đại Hải Trình tìm kiếm kho báu huyền thoại của Vua Hải Tặc tương lai.',
    figureCount: 45,
    themeColor: '#D6284F',
  },
  {
    id: 'demon-slayer',
    slug: 'demon-slayer',
    title: 'Demon Slayer',
    japaneseTitle: '鬼滅の刃',
    characterCount: 24,
    bannerImage: LOGO.demonSlayer,
    coverImage: LOGO.demonSlayer,
    description: 'Hơi Thở Mặt Trời và Sát Quỷ Đội trong cuộc chiến bảo vệ nhân loại khỏi chúa quỷ Muzan.',
    figureCount: 32,
    themeColor: '#147A4B',
  },
  {
    id: 'spy-family',
    slug: 'spy-x-family',
    title: 'Spy x Family',
    japaneseTitle: 'スパイファミリー',
    characterCount: 8,
    bannerImage: LOGO.spyFamily,
    coverImage: LOGO.spyFamily,
    description: 'Gia đình Forger dí dỏm, bí ẩn với điệp viên Twilight, sát thủ Yor và bé Anya có siêu năng lực.',
    figureCount: 16,
    themeColor: '#FFE6ED',
  },
];
