const base =
  (import.meta.env.PUBLIC_API_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000';

export interface HomeSection {
  type: string;
  enabled?: boolean;
  sort_order?: number;
  eyebrow?: string;
  headline?: string;
  cta_label?: string;
  cta_target?: string;
  [key: string]: unknown;
}

export interface HomeContent {
  schema_version: number;
  sections: HomeSection[];
}

/** Published homepage CMS content. Null khi backend offline/chưa có bản published. */
export async function getHomeContent(): Promise<HomeContent | null> {
  try {
    const res = await fetch(`${base}/api/v1/pages/home`, { headers: { Accept: 'application/json' } });
    if (!res.ok) return null;
    const json = await res.json();
    const data = json?.data;
    if (!data || !Array.isArray(data.sections)) return null;
    return data as HomeContent;
  } catch {
    return null;
  }
}

export function findSection(content: HomeContent | null, type: string): HomeSection | null {
  if (!content) return null;
  return content.sections.find((s) => s.type === type) ?? null;
}

/** Section mac dinh HIEN khi khong co CMS hoac thieu flag (giữ storefront cũ). */
export function isSectionEnabled(content: HomeContent | null, type: string): boolean {
  const section = findSection(content, type);
  if (!section) return true;
  return section.enabled !== false;
}
