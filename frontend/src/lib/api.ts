import type { FigureCategoryType, Product, StockStatusType } from '../types';

// Base URL cua Laravel API. SSR (build/dev server) dung localhost truc tiep.
export const API_URL =
  (import.meta.env.PUBLIC_API_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000';

const api = (path: string) => `${API_URL}/api/v1${path}`;

// ---- Raw API shapes ----
export interface ApiVariant {
  id: number;
  sku: string;
  label: string;
  attributes: Record<string, unknown>;
  price_amount: number;
  compare_at_amount: number | null;
  currency: string;
}

export interface ApiProductItem {
  id: string;
  name: string;
  slug: string;
  price_amount: number | null;
  currency: string;
  series: { name: string; slug: string } | null;
  manufacturer: { name: string; slug: string } | null;
  image: string | null;
  availability: 'in_stock' | 'sold_out' | 'pre_order';
}

export interface ApiProductDetail extends Omit<ApiProductItem, 'price_amount'> {
  short_description: string | null;
  description: string | null;
  attributes: Record<string, unknown>;
  categories: { name: string; slug: string }[];
  variants: ApiVariant[];
  gallery: { url: string | null; alt: string | null; is_primary: boolean }[];
}

const CATEGORY_MAP: Record<string, FigureCategoryType> = {
  scale: 'scale',
  'scale-figures': 'scale',
  nendoroid: 'nendoroid',
  action: 'action',
  'action-figures': 'action',
  model_kit: 'model_kit',
  'model-kits': 'model_kit',
  cosplay: 'cosplay',
};

export function mapCategory(slug: string | undefined): FigureCategoryType {
  return (slug && CATEGORY_MAP[slug]) || 'scale';
}

/** API availability -> storefront stock badge. */
function mapAvailability(availability: string | undefined): StockStatusType {
  return availability === 'sold_out' || availability === 'pre_order' ? availability : 'in_stock';
}

/** Map API product -> storefront Product (gi form hien thi, them defaultVariantId de checkout). */
export function toStoreProduct(
  p: ApiProductItem | ApiProductDetail,
  detail?: ApiProductDetail,
): Product {
  const d = detail ?? (p as ApiProductDetail);
  const variants = d.variants ?? [];
  const first = variants[0];
  const price = first?.price_amount ?? ('price_amount' in p ? p.price_amount : null) ?? 0;
  // Sản phẩm chưa có ảnh (VD: 3 món demo Gojo/Megumi/Nobara) → placeholder nội bộ
  // để <img> không bao giờ vỡ khung (broken icon + alt text tràn card).
  const gallery =
    (d.gallery ?? []).map((g) => g.url).filter((u): u is string => !!u).length > 0
      ? (d.gallery ?? []).map((g) => g.url).filter((u): u is string => !!u)
      : p.image
        ? [p.image]
        : ['/images/product-placeholder.svg'];
  const catSlug = d.categories?.[0]?.slug;
  const attrs = (d.attributes ?? {}) as Record<string, string>;
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    vietnameseTitle: d.short_description || p.name,
    series: p.series?.name ?? 'Figure',
    seriesSlug: p.series?.slug ?? 'figure',
    character: p.name,
    manufacturer: p.manufacturer?.name ?? 'Chính hãng',
    category: mapCategory(catSlug),
    categoryName: d.categories?.[0]?.name ?? 'Mô hình',
    scale: typeof attrs.scale === 'string' ? attrs.scale : undefined,
    material: typeof attrs.material === 'string' ? attrs.material : undefined,
    releaseDate: 'Hàng có sẵn',
    price,
    originalPrice: first?.compare_at_amount ?? undefined,
    status: mapAvailability(p.availability),
    stockCount: Math.max(variants.length, 1) * 5,
    isFeatured: false,
    isTrending: false,
    isNewArrival: true,
    rating: 5.0,
    reviewCount: 0,
    images: gallery,
    description: d.description || d.short_description || p.name,
    specifications: Object.fromEntries(
      Object.entries(attrs).map(([k, v]) => [k, String(v)]),
    ),
    tags: [p.series?.name ?? 'Figure'],
    defaultVariantId: first?.id,
    defaultSku: first?.sku,
  };
}

async function getJSON<T>(path: string, init?: RequestInit): Promise<T | null> {
  try {
    const res = await fetch(api(path), { ...init, headers: { Accept: 'application/json', ...(init?.headers || {}) } });
    if (!res.ok) return null;
    return (await res.json()) as T;
  } catch {
    return null;
  }
}

export interface Paginated<T> {
  data: T[];
  meta?: { total?: number };
}

/** Returns null when the API is unreachable, [] when it answered with no rows. */
export async function fetchProducts(params: Record<string, string | number> = {}): Promise<ApiProductItem[] | null> {
  const qs = new URLSearchParams(
    Object.entries(params).map(([k, v]) => [k, String(v)]),
  ).toString();
  const json = await getJSON<Paginated<ApiProductItem>>(`/products${qs ? `?${qs}` : ''}`);
  return json ? json.data ?? [] : null;
}

export async function fetchProductDetail(slug: string): Promise<ApiProductDetail | null> {
  const json = await getJSON<{ data: ApiProductDetail }>(`/products/${encodeURIComponent(slug)}`);
  return json?.data ?? null;
}

export async function fetchMeta(
  path: '/categories' | '/anime-series' | '/manufacturers',
): Promise<{ name: string; slug: string }[]> {
  const json = await getJSON<{ data: { name: string; slug: string }[] }>(path);
  return json?.data ?? [];
}

export interface ApiCampaign {
  id: number;
  variant_id: number;
  status: string;
  quota: number;
  reserved_quantity: number;
  deposit_per_unit_amount: number;
  sale_price_per_unit_amount: number;
  available: number;
  variant?: { id: number; sku: string; label: string; product?: { name: string; slug: string } | null } | null;
}

export async function fetchPreorders(): Promise<ApiCampaign[]> {
  const json = await getJSON<{ data: ApiCampaign[] }>('/preorders');
  return json?.data ?? [];
}

// ---- Checkout ----
function readXsrf(): string {
  if (typeof document === 'undefined') return '';
  const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
  return m ? decodeURIComponent(m[1]) : '';
}

async function csrfHeaders(): Promise<Record<string, string>> {
  if (typeof document !== 'undefined' && !readXsrf()) {
    await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' }).catch(() => {});
  }
  const token = readXsrf();
  return token ? { 'X-XSRF-TOKEN': token } : {};
}

export function getGuestToken(): string {
  if (typeof window === 'undefined') return '';
  let t = localStorage.getItem('wibu_guest_token');
  if (!t) {
    t = (crypto as Crypto).randomUUID
      ? crypto.randomUUID()
      : `guest-${Date.now()}-${Math.floor(Math.random() * 1e9)}`;
    localStorage.setItem('wibu_guest_token', t);
  }
  return t;
}

export interface CheckoutPayload {
  items: { variant_id: number; quantity: number; preorder_campaign_id?: number }[];
  customer: { name: string; email: string; phone: string };
  customer_note?: string;
  checkout_type?: 'regular' | 'preorder';
  payment_method: 'cod' | 'bank_transfer';
  coupon_code?: string;
  addresses: { type: 'shipping'; recipient_name: string; phone: string; line1: string }[];
}

export async function createOrder(payload: CheckoutPayload): Promise<{ ok: boolean; order?: { order_number: string; public_id: string }; error?: string }> {
  try {
    const key =
      (crypto as Crypto).randomUUID?.() ?? `order-${Date.now()}-${Math.floor(Math.random() * 1e9)}`;
    const res = await fetch(api('/orders'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'Idempotency-Key': key, ...(await csrfHeaders()) },
      body: JSON.stringify({ checkout_type: 'regular', ...payload }),
    });
    const json = await res.json();
    if (!res.ok) return { ok: false, error: json?.error?.message || `Lỗi ${res.status}` };
    return { ok: true, order: { order_number: json.data.order_number, public_id: json.data.id } };
  } catch {
    return { ok: false, error: 'Không kết nối được máy chủ. Kiểm tra backend :8000.' };
  }
}

export interface PreviewTotals {
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
  deposit_due: number;
}

export async function previewTotals(payload: Omit<CheckoutPayload, 'customer' | 'addresses'>): Promise<PreviewTotals | null> {
  try {
    const res = await fetch(api('/checkout/preview'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(await csrfHeaders()) },
      body: JSON.stringify({ checkout_type: 'regular', ...payload }),
    });
    if (!res.ok) return null;
    const json = await res.json();
    return json?.data ?? null;
  } catch {
    return null;
  }
}
