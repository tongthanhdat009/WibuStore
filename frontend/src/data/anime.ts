import type { AnimeSeries } from '../types';

// Anh bia series = logo anime chinh thuc, tu host tren chinh domain
// (/images/series/...) thay cho hotlink Wikimedia (tranh third-party cookie
// WMF-Uniq/NetworkProbeLimit lam rot diem Best Practices + them latency).
// SVG goc tu Wikimedia Commons (CC); ban PNG da resize ve 360px.
// logoOnDark = true: logo sang mau (trang/xam) → component tu dat len nen toi.
// NOTE: Chainsaw Man va Evangelion da bo khoi danh sach vi DB chua co
// san pham nao thuoc 2 series nay (trang /anime/... trong tron).
// Khi nao co hang thi them entry + logo vao day va LOGO la xong.
const LOGO = {
  jjk: '/images/series/jjk.svg',
  onePiece: '/images/series/one-piece-360.webp',
  demonSlayer: '/images/series/demon-slayer-360.webp',
  spyFamily: '/images/series/spy-family.webp',
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
