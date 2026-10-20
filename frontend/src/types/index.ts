export type StockStatusType = 'in_stock' | 'pre_order' | 'sold_out' | 'coming_soon';

export type FigureCategoryType = 'scale' | 'nendoroid' | 'action' | 'model_kit';

export interface ProductVariant {
  id: string;
  name: string;
  sku: string;
  price: number;
  originalPrice?: number;
  inStock: boolean;
}

export interface Product {
  id: string;
  slug: string;
  name: string;
  vietnameseTitle: string;
  series: string;
  seriesSlug: string;
  character: string;
  manufacturer: string;
  category: FigureCategoryType;
  categoryName: string;
  scale?: string;
  dimensions?: string;
  material?: string;
  sculptor?: string;
  releaseDate: string; // e.g. "Tháng 12/2026"
  price: number;
  originalPrice?: number;
  depositPrice?: number;
  status: StockStatusType;
  stockCount: number;
  isFeatured?: boolean;
  isTrending?: boolean;
  isNewArrival?: boolean;
  rating: number;
  reviewCount: number;
  images: string[];
  description: string;
  specifications: Record<string, string>;
  preOrderTerms?: string;
  tags: string[];
  defaultVariantId?: number;
  defaultSku?: string;
}

export interface HeroCampaign {
  id: string;
  issueNumber: string; // e.g. "ISSUE 001"
  badge: string; // e.g. "FEATURED DROP", "NEW ARRIVAL", "EXCLUSIVE PRE-ORDER"
  displayEnglishHeadline: string; // e.g. "YOUR NEXT OBSESSION."
  vietnameseHeading: string; // e.g. "Khắc họa uy lực tối thượng của Ngũ Điều Thầy"
  productName: string;
  series: string;
  manufacturer: string;
  scale: string;
  price: number;
  depositPrice?: number;
  status: StockStatusType;
  figureImage: string; // transparent cutout
  panelColor: 'yellow' | 'blue' | 'pink' | 'mint';
  panelBgClass: string;
  productSlug: string;
  primaryCtaText: string;
  primaryCtaLink: string;
  secondaryCtaText: string;
  secondaryCtaLink: string;
  thumbnailImage: string;
}

export interface CategoryInfo {
  id: FigureCategoryType;
  slug: string;
  name: string;
  englishTitle: string;
  description: string;
  image: string;
  badge: string;
  accentColor: string;
  itemCount: number;
}

export interface AnimeSeries {
  id: string;
  slug: string;
  title: string;
  japaneseTitle?: string;
  characterCount: number;
  bannerImage: string;
  coverImage: string;
  description: string;
  figureCount: number;
  themeColor: string;
}

export interface Collection {
  id: string;
  slug: string;
  title: string;
  tagline: string;
  description: string;
  coverImage: string;
  bannerImage: string;
  accentColor: string;
  productSlugs: string[];
  badge: string;
}

export interface CartItem {
  id: string;
  productId: string;
  variantId?: number;
  preorderCampaignId?: number;
  slug: string;
  name: string;
  series: string;
  image: string;
  price: number;
  depositPrice?: number;
  quantity: number;
  status: StockStatusType;
  isPreOrder: boolean;
  scale?: string;
}

export interface FilterOptions {
  category?: FigureCategoryType[];
  series?: string[];
  status?: StockStatusType[];
  manufacturer?: string[];
  minPrice?: number;
  maxPrice?: number;
  sortBy?: 'newest' | 'price_asc' | 'price_desc' | 'popular';
}
