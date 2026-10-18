import type { AstroGlobal } from 'astro';

export const API_URL =
  (import.meta.env.PUBLIC_API_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000';

const api = (path: string) => `${API_URL}/api/v1${path}`;

export interface ApiError {
  status: number;
  code?: string;
  message?: string;
}

export interface PageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

/** SSR: goi API giu session cookie cua browser. Tra ve { data } hoac { error }. */
export async function apiGet<T>(path: string, astro?: AstroGlobal): Promise<{ data?: T; meta?: PageMeta; error?: ApiError }> {
  try {
    const headers: Record<string, string> = { Accept: 'application/json' };
    const cookie = astro?.request.headers.get('cookie');
    if (cookie) {
      headers.Cookie = cookie;
      // SSR: khai bao origin first-party de Sanctum khoi dong session
      // (browser khong gui Origin/Referer khi tai trang truc tiep).
      const origin = astro?.url.origin;
      if (origin) {
        headers.Origin = origin;
        headers.Referer = `${origin}/`;
      }
    }
    const res = await fetch(api(path), { headers });
    if (res.status === 401) return { error: { status: 401, code: 'UNAUTHENTICATED' } };
    if (res.status === 403) return { error: { status: 403, code: 'FORBIDDEN' } };
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { error: { status: res.status, code: body?.error?.code, message: body?.error?.message } };
    }
    const body = await res.json();
    const meta: PageMeta | undefined =
      body && typeof body === 'object' && typeof body.current_page === 'number'
        ? {
            current_page: body.current_page,
            last_page: body.last_page ?? 1,
            per_page: body.per_page ?? 20,
            total: body.total ?? 0,
            from: body.from ?? null,
            to: body.to ?? null,
          }
        : undefined;
    return { data: (body.data ?? body) as T, meta };
  } catch {
    return { error: { status: 0, code: 'NETWORK', message: 'Khong ket noi duoc API. Kiem tra backend :8000.' } };
  }
}

export function currentPage(astro: AstroGlobal): number {
  const p = Number(astro.url.searchParams.get('page') || 1);
  return Number.isFinite(p) && p > 0 ? Math.floor(p) : 1;
}

export interface StaffMe {
  id: string;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
}

/** SSR guard: tra ve staff hoac Response redirect. Dung dau moi trang admin. */
export async function requireStaff(astro: AstroGlobal, loginPath = '/login') {
  const { data, error } = await apiGet<StaffMe>('/me', astro);
  if (!data) {
    return { redirect: astro.redirect(error?.status === 403 ? '/403' : loginPath), staff: null as StaffMe | null };
  }
  return { redirect: null, staff: data };
}

function xsrfToken(): string {
  if (typeof document === 'undefined') return '';
  const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
  return m ? decodeURIComponent(m[1]) : '';
}

/** Client: dam bao co CSRF cookie truoc POST/PATCH/PUT/DELETE. */
export async function mutate<T>(path: string, method: string, body?: unknown): Promise<{ data?: T; error?: ApiError }> {
  try {
    if (!xsrfToken()) {
      await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' });
    }
    const res = await fetch(api(path), {
      method,
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken(),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const payload = await res.json().catch(() => null);
    if (!res.ok) {
      return { error: { status: res.status, code: payload?.error?.code, message: payload?.error?.message || `Loi ${res.status}` } };
    }
    return { data: (payload?.data ?? payload) as T };
  } catch {
    return { error: { status: 0, message: 'Khong ket noi duoc API.' } };
  }
}

/** Client: login + logout dung session cookie. */
export async function staffLogin(email: string, password: string) {
  await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' });
  return mutate<{ id: string; name: string; roles: string[] }>('/auth/login', 'POST', { email, password });
}

export async function staffLogout() {
  const r = await mutate('/auth/logout', 'POST');
  return r;
}

/** Client: guard cho cac trang bao mat boi JS (goi trong <script>). */
export async function guardClient(adminOnly = true): Promise<StaffMe | null> {
  try {
    const res = await fetch(api('/me'), { credentials: 'include', headers: { Accept: 'application/json' } });
    if (!res.ok) {
      window.location.href = '/login';
      return null;
    }
    const { data } = await res.json();
    if (adminOnly && !data.roles?.some((r: string) => r !== 'customer')) {
      window.location.href = '/403';
      return null;
    }
    return data as StaffMe;
  } catch {
    window.location.href = '/login';
    return null;
  }
}
