import type { CartItem, Product } from '../types';

const CART_STORAGE_KEY = 'wibu_store_cart_v2';

export function getStoredCart(): CartItem[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(CART_STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Failed to parse cart storage', e);
    return [];
  }
}

export function saveCart(items: CartItem[]): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
    window.dispatchEvent(new CustomEvent('wibu-cart-change', { detail: { items } }));
  } catch (e) {
    console.error('Failed to save cart storage', e);
  }
}

export function addToCartItem(product: Product, quantity = 1): CartItem[] {
  const current = getStoredCart();
  const existingIdx = current.findIndex((item) => item.productId === product.id);

  if (existingIdx > -1) {
    current[existingIdx].quantity += quantity;
  } else {
    current.push({
      id: `${product.id}-${Date.now()}`,
      productId: product.id,
      variantId: product.defaultVariantId,
      slug: product.slug,
      name: product.name,
      series: product.series,
      image: product.images[0] || '',
      price: product.price,
      depositPrice: product.depositPrice,
      quantity,
      status: product.status,
      isPreOrder: product.status === 'pre_order',
      scale: product.scale,
    });
  }

  saveCart(current);
  return current;
}

export function updateCartQuantity(productId: string, quantity: number): CartItem[] {
  const current = getStoredCart();
  const index = current.findIndex((item) => item.productId === productId || item.id === productId);
  if (index > -1) {
    if (quantity <= 0) {
      current.splice(index, 1);
    } else {
      current[index].quantity = quantity;
    }
  }
  saveCart(current);
  return current;
}

export function removeCartItem(productId: string): CartItem[] {
  let current = getStoredCart();
  current = current.filter((item) => item.productId !== productId && item.id !== productId);
  saveCart(current);
  return current;
}

export function clearCart(): void {
  saveCart([]);
}

const API_BASE =
  (import.meta.env.PUBLIC_API_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000';

export async function syncItemToServer(variantId: number, quantity: number): Promise<boolean> {
  try {
    const res = await fetch(`${API_BASE}/api/v1/cart/items`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ variant_id: variantId, quantity }),
    });
    return res.ok;
  } catch {
    return false;
  }
}

export async function syncLocalCartToServer(): Promise<void> {
  const items = getStoredCart().filter((i) => i.variantId);
  for (const item of items) {
    await syncItemToServer(item.variantId as number, item.quantity).catch(() => {});
  }
}
