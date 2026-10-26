import type { Collection } from '../types';

const S3 = 'https://s3-nexpress-us1.vyta.io.vn/shiphard-studio/Dat/wibu-store/public/products/seed';

// productSlugs PHẢI là slug có thật trong DB (GET /api/v1/products) và có ảnh,
// nếu không block "featured collection" ở homepage và trang /collections/[slug]
// sẽ render trống. Ảnh bìa dùng luôn ảnh S3 thật của sản phẩm trong bộ.
export const collections: Collection[] = [
  {
    id: 'shibuya-incident-drop',
    slug: 'shibuya-incident-drop',
    title: 'Shibuya Incident Drop',
    tagline: 'BIẾN CỐ SHIBUYA — SỰ THỨC TỈNH CỦA ĐỆ NHẤT CHÚ THUẬT SƯ',
    description: 'Tuyển tập các mô hình cao cấp tái hiện những khoảnh khắc bi tráng nhất trong arc Biến Cố Shibuya của Jujutsu Kaisen.',
    coverImage: `${S3}/figure/4%201.jpg`,
    bannerImage: `${S3}/figure/4%201.jpg`,
    accentColor: '#3258D7',
    badge: 'LIMITED CAMPAIGN',
    productSlugs: ['figure-4-seed'],
  },
  {
    id: 'wano-kuni-climax',
    slug: 'wano-kuni-climax',
    title: 'Wano Kuni Climax — Gear 5',
    tagline: 'THẦN MẶT TRỜI NIKA GIÁNG THẾ TRÊN BẦU TRỜI ONIGASHIMA',
    description: 'Bộ sưu tập những tuyệt tác mô hình One Piece đỉnh điểm trận chiến Kaido vs Luffy Thần Mặt Trời.',
    coverImage: `${S3}/set_figure/40%201.jpg`,
    bannerImage: `${S3}/set_figure/39%201.jpg`,
    accentColor: '#D6284F',
    badge: 'HOT ARCHIVE',
    productSlugs: ['set-figure-40-seed', 'set-figure-39-seed', 'set-figure-31-seed'],
  },
  {
    id: 'tokyo-pop-collectibles',
    slug: 'tokyo-pop-collectibles',
    title: 'Hatsune Miku Symphony',
    tagline: 'DIVA ẢO HUYỀN THOẠI TRONG NHỮNG BẢN PHỐI MÀU SÂN KHẤU RỰC RỠ',
    description: 'Các tác phẩm scale figure Hatsune Miku độc bản theo phong cách pop-art hiện đại.',
    coverImage: `${S3}/set_figure/36%201.jpg`,
    bannerImage: `${S3}/set_figure/35%201.jpg`,
    accentColor: '#FFD44A',
    badge: 'EXCLUSIVE EDITORIAL',
    productSlugs: ['set-figure-36-seed', 'set-figure-35-seed', 'figure-13-seed'],
  },
];
