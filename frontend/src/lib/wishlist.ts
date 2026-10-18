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
  return !exists;
}

export function isInWishlist(productId: string): boolean {
  const current = getStoredWishlist();
  return current.includes(productId);
}
