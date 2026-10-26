import { API_URL } from './api';

export interface AiProduct {
  name: string;
  slug: string;
  price_amount: number | null;
  currency: string;
  series?: { name: string; slug: string } | null;
  image?: string | null;
  images?: string[];
  availability: string;
  url: string;
}

export interface AiChatReply {
  reply: string;
  model: string;
  products?: AiProduct[];
}

export interface AiVisionReply {
  description: string;
  model: string;
  mime_type: string;
}

export interface AiTryOnReply {
  url: string;
  mime_type: string;
  model: string;
  media_asset_id: number;
}

export type AiResult<T> = { ok: true; data: T } | { ok: false; error: string };

function friendlyError(status: number, fallback: string): string {
  if (status === 429) return 'Bạn thao tác quá nhanh. Đợi khoảng 1 phút rồi thử lại nhé.';
  if (status === 502) return 'AI đang bận hoặc chưa khả dụng. Thử lại sau ít phút.';
  if (status === 422) return 'Ảnh chưa hợp lệ (chỉ nhận JPG/PNG/WEBP dưới 5MB).';
  return fallback;
}

async function parseJson<T>(res: Response): Promise<AiResult<T>> {
  try {
    const json = await res.json();
    if (!res.ok) {
      const msg = (json as { error?: { message?: string } })?.error?.message;
      return { ok: false, error: typeof msg === 'string' && msg ? msg : friendlyError(res.status, `Lỗi ${res.status}`) };
    }
    return { ok: true, data: (json as { data: T }).data };
  } catch {
    return { ok: false, error: 'Không đọc được phản hồi từ máy chủ.' };
  }
}

/** Chat text với trợ lý Wibu Store. Gửi kèm lịch sử để LLM nhớ ngữ cảnh. */
export interface AiHistoryItem {
  role: 'user' | 'ai';
  text: string;
}

export async function postAiChat(message: string, history: AiHistoryItem[] = []): Promise<AiResult<AiChatReply>> {
  try {
    const res = await fetch(`${API_URL}/api/v1/ai/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        // Loc tu nhay cam voi Google filter trong lich su cu (waifu/senpai) de khoi bi chan.
        message: message.replace(/waifu/gi, 'tro ly').replace(/senpai/gi, 'ban'),
        history: history.slice(-20).map((h) => ({
          role: h.role,
          text: h.text.replace(/waifu/gi, 'tro ly').replace(/senpai/gi, 'ban').slice(0, 1000),
        })),
      }),
    });
    return parseJson<AiChatReply>(res);
  } catch {
    return { ok: false, error: 'Không kết nối được máy chủ. Kiểm tra backend :8000.' };
  }
}

/** Đọc/mô tả 1 ảnh. */
export async function postAiVision(image: File, prompt?: string): Promise<AiResult<AiVisionReply>> {
  try {
    const form = new FormData();
    form.append('image', image);
    if (prompt?.trim()) form.append('prompt', prompt.trim().slice(0, 500));
    const res = await fetch(`${API_URL}/api/v1/ai/vision`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: form,
    });
    return parseJson<AiVisionReply>(res);
  } catch {
    return { ok: false, error: 'Không kết nối được máy chủ. Kiểm tra backend :8000.' };
  }
}

/** Ghép trang phục: ảnh người + 1-3 ảnh trang phục/phụ kiện. */
export async function postAiTryOn(
  person: File,
  outfits: File[],
  prompt?: string,
): Promise<AiResult<AiTryOnReply>> {
  try {
    const form = new FormData();
    form.append('person', person);
    outfits.slice(0, 3).forEach((f) => form.append('outfits[]', f));
    if (prompt?.trim()) form.append('prompt', prompt.trim().slice(0, 300));
    const res = await fetch(`${API_URL}/api/v1/ai/try-on`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: form,
    });
    return parseJson<AiTryOnReply>(res);
  } catch {
    return { ok: false, error: 'Không kết nối được máy chủ. Kiểm tra backend :8000.' };
  }
}

/** Từ khóa gợi ý từ câu mô tả (bỏ stopwords tiếng Việt, giữ từ dài). */
const VI_STOPWORDS = new Set(
  'và của là có trong một những các với cho từ đến như này kia đó đây rất cũng được bị để khi mà thì là ở tại về lên xuống ra vào bức ảnh này có một chiếc cái người mặc trên dưới giữa nền trước sau bên cạnh nhau rất khá'.split(' '),
);

export function keywordsFromDescription(text: string, max = 3): string[] {
  const words = text
    .toLowerCase()
    .replace(/[^\p{L}\p{N}\s]/gu, ' ')
    .split(/\s+/)
    .filter((w) => w.length > 3 && !VI_STOPWORDS.has(w));
  const uniq = [...new Set(words)];
  uniq.sort((a, b) => b.length - a.length);
  return uniq.slice(0, max);
}
