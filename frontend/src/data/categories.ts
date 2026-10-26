import type { CategoryInfo } from '../types';

const S3 = 'https://s3-nexpress-us1.vyta.io.vn/shiphard-studio/Dat/wibu-store/public/products/seed';

// Ảnh thẻ = ảnh sản phẩm THẬT trong DB thuộc đúng dòng (verify 200),
// thay cho ảnh Unsplash chung chung trước đây.
export const categories: CategoryInfo[] = [
  {
    id: 'scale',
    slug: 'scale-figures',
    name: 'Scale Figures',
    englishTitle: 'PREMIUM SCALE',
    description: 'Mô hình tỉ lệ 1/7, 1/4, 1/8 được hoàn thiện tinh xảo từng chi tiết sắc nét từ các studio Nhật Bản.',
    image: `${S3}/set_figure/40%201.jpg`,
    badge: '1/7 & 1/4',
    accentColor: '#FFD44A',
    itemCount: 42,
  },
  {
    id: 'nendoroid',
    slug: 'nendoroid',
    name: 'Nendoroid & Chibi',
    englishTitle: 'CHIBI OBSESSION',
    description: 'Dòng mô hình khớp mini đáng yêu từ Good Smile Company với các biểu cảm và phụ kiện phong phú.',
    image: `${S3}/chibi_figure/16%201.jpg`,
    badge: 'GOOD SMILE',
    accentColor: '#FFE6ED',
    itemCount: 38,
  },
  {
    id: 'action',
    slug: 'action-figures',
    name: 'Action Figures',
    englishTitle: 'DYNAMIC POSE',
    description: 'Mô hình khớp cử động linh hoạt Figma, S.H.Figuarts, Revoltech cho phép tái hiện mọi thế đánh manga.',
    image: `${S3}/figure/4%201.jpg`,
    badge: 'FIGMA & SHF',
    accentColor: '#DDF9EB',
    itemCount: 29,
  },
  {
    id: 'cosplay',
    slug: 'cosplay',
    name: 'Trang phục Cosplay',
    englishTitle: 'COSPLAY ZONE',
    description: 'Trang phục, tóc giả và phụ kiện cosplay chuẩn nguyên tác từ Naruto, One Piece, Jujutsu Kaisen.',
    image: `${S3}/cosplay/50%201.jpg`,
    badge: 'COSTUME',
    accentColor: '#E9D5FF',
    itemCount: 6,
  },
];
