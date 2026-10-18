import { products as staticProducts } from '../data/products';
import type { Product } from '../types';
import { fetchProductDetail, fetchProducts, toStoreProduct } from './api';

/** API-first, fallback static khi backend offline (dev/build van chay). */
export async function getCatalogProducts(params: Record<string, string | number> = {}): Promise<{ products: Product[]; live: boolean }> {
  try {
    const items = await fetchProducts({ per_page: 48, ...params });
    if (items.length > 0) {
      const detailed = await Promise.all(
        items.slice(0, 24).map(async (it) => {
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
          { id: d.id, name: d.name, slug: d.slug, price_amount: d.variants[0]?.price_amount ?? 0, currency: 'VND', series: d.series, manufacturer: d.manufacturer, image: d.gallery[0]?.url ?? null, availability: 'in_stock' },
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

export async function getAllSlugs(): Promise<string[]> {
  try {
    const slugs: string[] = [];
    for (let page = 1; page <= 10; page++) {
      const items = await fetchProducts({ per_page: 48, page });
      if (items.length === 0) break;
      slugs.push(...items.map((i) => i.slug));
      if (items.length < 48) break;
    }
    if (slugs.length > 0) return slugs;
  } catch {
  }
  return staticProducts.map((p) => p.slug);
}
