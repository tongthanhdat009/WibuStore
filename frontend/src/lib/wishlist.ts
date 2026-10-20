const WISHLIST_STORAGE_KEY = 'wibu_store_wishlist_v2';

export function getStoredWishlist(): string[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(WISHLIST_STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Failed to parse wishlist storage', e);
    return [];
  }
}

export function saveWishlist(ids: string[]): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(WISHLIST_STORAGE_KEY, JSON.stringify(ids));
    window.dispatchEvent(new CustomEvent('wibu-wishlist-change', { detail: { ids } }));
  } catch (e) {
    console.error('Failed to save wishlist storage', e);
  }
}

const WISH_API =
  (import.meta.env.PUBLIC_API_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000';

async function pushWishlist(productId: string, add: boolean): Promise<void> {
  try {
    await fetch(`${WISH_API}/api/v1/wishlist${add ? '' : `/${encodeURIComponent(productId)}`}`, {
      method: add ? 'POST' : 'DELETE',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: add ? JSON.stringify({ product_id: productId }) : undefined,
    });
  } catch {
  }
}

export function toggleWishlist(productId: string): boolean {
  const current = getStoredWishlist();
  const exists = current.includes(productId);
  let updated: string[];
  if (exists) {
    updated = current.filter((id) => id !== productId);
  } else {
    updated = [...current, productId];
  }
  saveWishlist(updated);
  pushWishlist(productId, !exists);
  return !exists;
}

export async function syncLocalWishlistToServer(): Promise<void> {
  for (const id of getStoredWishlist()) {
    await pushWishlist(id, true);
  }
}

export interface ServerWishlistItem {
  id: string;
  name: string;
  slug: string;
  price_amount: number | null;
  image: string | null;
}

export async function fetchServerWishlist(): Promise<ServerWishlistItem[] | null> {
  try {
    const res = await fetch(`${WISH_API}/api/v1/wishlist`, {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });
    if (res.status === 401) return null;
    if (!res.ok) return null;
    const json = await res.json();
    return (json?.data ?? []) as ServerWishlistItem[];
  } catch {
    return null;
  }
}

export function isInWishlist(productId: string): boolean {
  const current = getStoredWishlist();
  return current.includes(productId);
}
