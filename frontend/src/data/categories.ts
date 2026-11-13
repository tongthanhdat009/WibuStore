import type { CategoryInfo } from '../types';

// Ảnh thẻ là bản webp 320px tạo từ ảnh sản phẩm THẬT trong DB (giảm ~85% dung
// lượng so với file S3 gốc, đủ nét cho thumbnail 96px), thay cho URL S3 full-size.
export const categories: CategoryInfo[] = [
  {
    id: 'scale',
    slug: 'scale-figures',
    name: 'Scale Figures',
    englishTitle: 'PREMIUM SCALE',
    description: 'Mô hình tỉ lệ 1/7, 1/4, 1/8 được hoàn thiện tinh xảo từng chi tiết sắc nét từ các studio Nhật Bản.',
    image: '/images/categories/scale-figures.webp',
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
    image: '/images/categories/nendoroid-chibi.webp',
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
    image: '/images/categories/action-figures.webp',
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
    image: '/images/categories/cosplay.webp',
    badge: 'COSTUME',
    accentColor: '#E9D5FF',
    itemCount: 6,
  },
];
