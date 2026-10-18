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
  availability: string;
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
  'scale-figure': 'scale',
  'scale-figure-seed': 'scale',
  nendoroid: 'nendoroid',
  'chibi-seed': 'nendoroid',
  action: 'action',
  'cosplay-seed': 'action',
  model_kit: 'model_kit',
  'set-figure-seed': 'model_kit',
};

function mapCategory(slug: string | undefined): FigureCategoryType {
  return (slug && CATEGORY_MAP[slug]) || 'scale';
}

/** Map API product -> storefront Product (gi form hien thi, them defaultVariantId de checkout). */
export function toStoreProduct(
  p: ApiProductItem | ApiProductDetail,
  detail?: ApiProductDetail,
): Product {
  const d = detail ?? (p as ApiProductDetail);
  const variants = d.variants ?? [];
  const first = variants[0];
  const price = first?.price_amount ?? p.price_amount ?? 0;
  const gallery =
    (d.gallery ?? []).map((g) => g.url).filter((u): u is string => !!u).length > 0
      ? (d.gallery ?? []).map((g) => g.url).filter((u): u is string => !!u)
      : p.image
        ? [p.image]
        : [];
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
    status: 'in_stock' as StockStatusType,
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

export async function fetchProducts(params: Record<string, string | number> = {}): Promise<ApiProductItem[]> {
  const qs = new URLSearchParams(
    Object.entries(params).map(([k, v]) => [k, String(v)]),
  ).toString();
  const json = await getJSON<Paginated<ApiProductItem>>(`/products${qs ? `?${qs}` : ''}`);
  return json?.data ?? [];
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
  items: { variant_id: number; quantity: number }[];
  customer: { name: string; email: string; phone: string };
  customer_note?: string;
  payment_method: 'cod' | 'bank_transfer';
  coupon_code?: string;
  addresses: { type: 'shipping'; recipient_name: string; phone: string; line1: string }[];
}

export async function createOrder(payload: CheckoutPayload): Promise<{ ok: boolean; order?: { order_number: string }; error?: string }> {
  try {
    const key =
      (crypto as Crypto).randomUUID?.() ?? `order-${Date.now()}-${Math.floor(Math.random() * 1e9)}`;
    const res = await fetch(api('/orders'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'Idempotency-Key': key },
      body: JSON.stringify({ ...payload, checkout_type: 'regular' }),
    });
    const json = await res.json();
    if (!res.ok) return { ok: false, error: json?.error?.message || `Lỗi ${res.status}` };
    return { ok: true, order: { order_number: json.data.order_number } };
  } catch {
    return { ok: false, error: 'Không kết nối được máy chủ. Kiểm tra backend :8000.' };
  }
}

export async function previewTotals(payload: Omit<CheckoutPayload, 'customer' | 'addresses'>): Promise<{ subtotal: number; discount: number; shipping: number; total: number } | null> {
  const json = await getJSON<{ data: { subtotal: number; shipping: number; total: number } }>('/checkout/preview', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...payload, checkout_type: 'regular' }),
  });
  return json?.data ?? null;
}
