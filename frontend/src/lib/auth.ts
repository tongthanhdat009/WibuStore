import { API_URL } from './api';

const api = (path: string) => `${API_URL}/api/v1${path}`;

function xsrf(): string {
  if (typeof document === 'undefined') return '';
  const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
  return m ? decodeURIComponent(m[1]) : '';
}

async function csrf() {
  if (!xsrf()) {
    await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' });
  }
}

export interface CustomerMe {
  id: string;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
}

async function post<T>(path: string, body: unknown): Promise<{ data?: T; error?: string }> {
  try {
    await csrf();
    const res = await fetch(api(path), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
      body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => null);
    if (!res.ok) return { error: json?.error?.message || `Lỗi ${res.status}` };
    return { data: (json?.data ?? json) as T };
  } catch {
    return { error: 'Không kết nối được máy chủ.' };
  }
}

async function get<T>(path: string): Promise<T | null> {
  try {
    const res = await fetch(api(path), { credentials: 'include', headers: { Accept: 'application/json' } });
    if (!res.ok) return null;
    const json = await res.json();
    return (json?.data ?? json) as T;
  } catch {
    return null;
  }
}

export const customerLogin = (email: string, password: string) => post<CustomerMe>('/auth/login', { email, password });
export const customerRegister = (name: string, email: string, password: string, password_confirmation: string, phone?: string) =>
  post<CustomerMe>('/auth/register', { name, email, password, password_confirmation, phone });
export const customerLogout = () => post('/auth/logout', {});
export const customerMe = () => get<CustomerMe>('/me');

export interface MyOrder {
  id: string;
  order_number: string;
  order_status: string;
  payment_status: string;
  total_amount: number;
  placed_at?: string | null;
}

export const myOrders = () => get<MyOrder[]>('/me/orders');
export const myAddresses = () => get<{ id: number; recipient_name: string; phone: string; line1: string; district?: string; province?: string }[]>('/me/addresses');

export const lookupOrder = (order_number: string, email: string) =>
  post<{ public_id: string; order_status: string; payment_status: string }>('/orders/lookup', { order_number, email });
