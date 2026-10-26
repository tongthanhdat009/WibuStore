import { products as staticProducts } from '../data/products';
import type { Product } from '../types';
import { fetchProductDetail, fetchProducts, mapCategory, toStoreProduct } from './api';

/** Apply the same filters the API would, to the static fallback set. */
function filterStatic(params: Record<string, string | number>): Product[] {
  let out = staticProducts;
  if (params.category !== undefined) {
    const type = mapCategory(String(params.category));
    out = out.filter((p) => p.category === type);
  }
  if (params.anime_series !== undefined) {
    out = out.filter((p) => p.seriesSlug === String(params.anime_series));
  }
  if (params.q !== undefined) {
    const q = String(params.q).toLowerCase();
    out = out.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.vietnameseTitle.toLowerCase().includes(q) ||
        p.character.toLowerCase().includes(q),
    );
  }
  return out;
}

/**
 * API-first, fallback static khi backend offline (dev/build van chay).
 *
 * The fallback is only for an unreachable API. A reachable API that answers with
 * zero rows means "no products match" and must NOT be replaced by the whole
 * static catalogue — that would show unrelated products on a filtered page.
 */
export async function getCatalogProducts(params: Record<string, string | number> = {}): Promise<{ products: Product[]; live: boolean }> {
  try {
    const items = await fetchProducts({ per_page: 48, ...params });
    if (items === null) {
      return { products: filterStatic(params), live: false };
    }
    const detailed = await Promise.all(
      items.slice(0, 24).map(async (it) => {
        const d = await fetchProductDetail(it.slug);
        return toStoreProduct(it, d ?? undefined);
      }),
    );
    return { products: detailed, live: true };
  } catch {
    return { products: filterStatic(params), live: false };
  }
}

/** Tat ca san pham qua nhieu trang (API cap 48/trang). Dung cho trang figures. */
export async function getAllCatalogProducts(): Promise<{ products: Product[]; live: boolean }> {
  try {
    const items: NonNullable<Awaited<ReturnType<typeof fetchProducts>>> = [];
    let reachable = false;
    for (let page = 1; page <= 10; page++) {
      const batch = await fetchProducts({ per_page: 48, page });
      if (batch === null) break;
      reachable = true;
      if (batch.length === 0) break;
      items.push(...batch);
      if (batch.length < 48) break;
    }
    if (reachable) {
      const detailed = await Promise.all(
        items.map(async (it) => {
          const d = await fetchProductDetail(it.slug);
          return toStoreProduct(it, d ?? undefined);
        }),
      );
      return { products: detailed, live: true };
    }
  } catch {
    /* fallback below */
  }
  return { products: staticProducts, live: false };
}

export async function getProductBySlug(slug: string): Promise<{ product: Product; live: boolean }> {
  try {
    const d = await fetchProductDetail(slug);
    if (d) {
      return {
        product: toStoreProduct(
          { id: d.id, name: d.name, slug: d.slug, price_amount: d.variants[0]?.price_amount ?? 0, currency: 'VND', series: d.series, manufacturer: d.manufacturer, image: d.gallery[0]?.url ?? null, availability: d.availability },
          d,
        ),
        live: true,
      };
    }
  } catch {
    /* fallback below */
  }
  const found = staticProducts.find((p) => p.slug === slug);
  if (!found) throw new Error(`Product not found: ${slug}`);
  return { product: found, live: false };
}

export interface TaxonomyMeta {
  name: string;
  slug: string;
  description?: string;
}

export async function fetchTaxonomy(
  path: '/categories' | '/anime-series',
): Promise<TaxonomyMeta[]> {
  try {
    const base = (import.meta.env.PUBLIC_API_URL as string | undefined) || 'http://localhost:8000';
    const res = await fetch(`${base}/api/v1${path}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    const json = await res.json();
    return (json?.data ?? []).map((c: { name: string; slug: string }) => ({ name: c.name, slug: c.slug }));
  } catch {
    return [];
  }
}

export async function getAllSlugs(): Promise<string[]> {
  try {
    const slugs: string[] = [];
    let reachable = false;
    for (let page = 1; page <= 10; page++) {
      const items = await fetchProducts({ per_page: 48, page });
      if (items === null) break;
      reachable = true;
      if (items.length === 0) break;
      slugs.push(...items.map((i) => i.slug));
      if (items.length < 48) break;
    }
    if (reachable && slugs.length > 0) return slugs;
  } catch {
  }
  return staticProducts.map((p) => p.slug);
}
