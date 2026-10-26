<?php

namespace App\Domain\Ai;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI-compatible chat proxy (9router default). Key stays server-side
 * (config/services.php `llm`, env LLM_API_KEY — never commit).
 *
 * Note: 9router answers in SSE (`data: {...}` chunks) even with
 * `stream: false`, so the body is parsed as a stream first.
 */
class LlmService
{
    /**
     * @param  array<int, array{role: string, text: string}>  $history  Lich su chat (role: user|ai|assistant), cu nhat truoc.
     */
    public static function generate(string $prompt, ?string $system = null, array $history = []): array
    {
        $base = rtrim(config('services.llm.base_url'), '/');
        $key = config('services.llm.api_key');
        $model = config('services.llm.chat_model', 'ag/gemini-3.8-flash-low');
        $timeout = max(10, (int) config('services.llm.timeout', 60));

        if (! $key) {
            throw new \RuntimeException('LLM chua cau hinh (thieu LLM_API_KEY).');
        }

        $messages = [];
        if ($system) {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        // Lich su: chi lay 10 cap gan nhat, chuan hoa role ai -> assistant.
        foreach (array_slice($history, -20) as $h) {
            $role = $h['role'] ?? '';
            $text = trim((string) ($h['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $messages[] = [
                'role' => $role === 'user' ? 'user' : 'assistant',
                'content' => mb_substr($text, 0, 1000),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            // Khong gui `stream` (9router mac dinh tra SSE; gui stream:false
            // lam ocg/muse-spark tra JSON rong). extractText doc duoc ca 2.
            // max_tokens chi gui khi cau hinh LLM_MAX_TOKENS, mac dinh de
            // provider tu quyet dinh gioi han output.
            $payload = ['model' => $model, 'messages' => $messages];
            if ($maxTokens = config('services.llm.max_tokens')) {
                $payload['max_tokens'] = (int) $maxTokens;
            }
            $res = Http::timeout($timeout)
                ->withToken($key)
                ->post($base.'/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('LlmService exception: '.$e->getMessage(), ['model' => $model]);

            throw new \RuntimeException('LLM tam thoi khong dung duoc.');
        }

        $body = (string) $res->body();

        if (! $res->successful()) {
            $msg = self::extractError($body) ?? 'LLM loi HTTP '.$res->status();
            Log::warning('LlmService failed: HTTP '.$res->status(), ['model' => $model]);

            throw new \RuntimeException($msg);
        }

        $parsed = self::extractText($body);
        if ($parsed === null) {
            throw new \RuntimeException('LLM tra ve rong.');
        }

        return ['text' => $parsed['text'], 'model' => $parsed['model'] ?? $model];
    }

    /** Parse either a JSON chat completion or an SSE stream into text. */
    private static function extractText(string $body): ?array
    {
        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '{')) {
            // 9router co the chen `data: [DONE]` dinh lien sau JSON (provider cf).
            $jsonPart = preg_replace('/data:\s*\[DONE\]\s*$/', '', $trimmed);
            $decoded = json_decode($jsonPart, true);
            $text = data_get($decoded, 'choices.0.message.content');
            if (is_string($text) && trim($text) !== '') {
                return [
                    'text' => trim($text),
                    'model' => data_get($decoded, 'model'),
                ];
            }

            return null;
        }

        $text = '';
        $model = null;
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, 'data:')) {
                continue;
            }
            $payload = trim(substr($line, 5));
            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }
            $chunk = json_decode($payload, true);
            if (! is_array($chunk)) {
                continue;
            }
            $model ??= $chunk['model'] ?? null;
            $delta = data_get($chunk, 'choices.0.delta.content', '')
                ?? data_get($chunk, 'choices.0.message.content', '');
            if (is_string($delta)) {
                $text .= $delta;
            }
        }

        return trim($text) !== '' ? ['text' => trim($text), 'model' => $model] : null;
    }

    private static function extractError(string $body): ?string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $msg = data_get($decoded, 'error.message');
            if (is_string($msg) && trim($msg) !== '') {
                return trim($msg);
            }
        }

        return null;
    }

    /**
     * Tao anh: 1 model duy nhat LLM_IMAGE_MODEL lam ca 2 viec.
     * - Text-to-image qua /v1/images/generations (prompt chu).
     * - Try-on (tryOn) qua /v1/chat/completions (anh nguoi + anh do).
     * Model phai tra duoc anh (VD ag/gemini-3.1-flash-image). SDXL cua
     * Cloudflare chi ve tu chu, khong doc anh input nen khong lam try-on.
     * Gemini chi song o VN, staging HK geo-block ca 2 chuc nang.
     *
     * @return array{bytes: string, mime: string, model: string}
     */
    /** Tach env model (cach nhau bang dau phay) thanh danh sach fallback. */
    private static function modelList(string $key, string $default): array
    {
        $raw = (string) config('services.llm.'.$key, $default);
        $list = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $list !== [] ? $list : [$default];
    }

    private static function imageModels(): array
    {
        return self::modelList('image_model', 'ag/gemini-3.1-flash-image');
    }

    public static function generateImage(string $prompt): array
    {
        $base = rtrim(config('services.llm.base_url'), '/');
        $key = config('services.llm.api_key');
        $models = self::imageModels();
        $timeout = max(30, (int) config('services.llm.timeout', 60) * 3);

        if (! $key) {
            throw new \RuntimeException('LLM chua cau hinh (thieu LLM_API_KEY).');
        }

        $lastError = 'LLM tam thoi khong dung duoc.';
        foreach ($models as $model) {
            try {
                $res = Http::timeout($timeout)
                    ->withToken($key)
                    ->post($base.'/images/generations', [
                        'model' => $model,
                        'prompt' => mb_substr($prompt, 0, 500),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('LlmService image exception: '.$e->getMessage(), ['model' => $model]);
                $lastError = 'LLM tam thoi khong dung duoc.';

                continue;
            }

            $body = (string) $res->body();
            if (! $res->successful()) {
                $lastError = self::extractError($body) ?? 'LLM loi HTTP '.$res->status();
                Log::warning('LlmService image failed: HTTP '.$res->status(), ['model' => $model]);

                continue;
            }

            $b64 = data_get(json_decode($body, true), 'data.0.b64_json');
            if (! is_string($b64) || $b64 === '') {
                $lastError = 'LLM tra ve anh rong.';

                continue;
            }

            $bytes = base64_decode($b64, true);
            $mime = $bytes !== false ? self::sniffImageMime($bytes) : null;
            if ($bytes === false || $mime === null) {
                $lastError = 'LLM tra ve anh loi.';

                continue;
            }

            return ['bytes' => $bytes, 'mime' => $mime, 'model' => $model];
        }

        throw new \RuntimeException($lastError);
    }

    /**
     * Ghep trang phuc: anh nguoi + 1..3 anh trang phuc/phu kien qua chat
     * (9router khong co /images/edits; model image gen tra anh dang
     * markdown `![image](data:...;base64,...)` trong message content).
     * Dung chung LLM_IMAGE_MODEL voi generateImage.
     *
     * @param  array<int, array{bytes: string, mime: string}>  $outfits
     * @return array{bytes: string, mime: string, model: string}
     */
    public static function tryOn(string $personBytes, string $personMime, array $outfits, ?string $prompt = null): array
    {
        $base = rtrim(config('services.llm.base_url'), '/');
        $key = config('services.llm.api_key');
        $models = self::imageModels();
        $timeout = max(60, (int) config('services.llm.timeout', 60) * 4);

        if (! $key) {
            throw new \RuntimeException('LLM chua cau hinh (thieu LLM_API_KEY).');
        }

        $valid = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($personMime, $valid, true)) {
            throw new \RuntimeException('Anh nguoi phai la JPEG, PNG hoac WebP.');
        }
        $outfits = array_values(array_filter(
            $outfits,
            fn ($o) => is_array($o) && in_array($o['mime'] ?? null, $valid, true) && ! empty($o['bytes'])
        ));
        if (count($outfits) < 1 || count($outfits) > 3) {
            throw new \RuntimeException('Can 1-3 anh trang phuc/phu kien.');
        }

        $extra = $prompt !== null && trim($prompt) !== ''
            ? ' Yeu cau them: '.mb_substr(trim($prompt), 0, 300)
            : '';
        $content = [[
            'type' => 'text',
            'text' => 'First image is a person, the rest '.(count($outfits) > 1 ? 'are outfit/accessory references' : 'is an outfit/accessory reference').'. '
                .'Generate a photorealistic image of that exact person wearing that outfit, keep the face, pose and background style natural.'.$extra.' '
                .'Return only the image.',
        ]];
        $content[] = ['type' => 'image_url', 'image_url' => [
            'url' => 'data:'.$personMime.';base64,'.base64_encode($personBytes),
        ]];
        foreach ($outfits as $o) {
            $content[] = ['type' => 'image_url', 'image_url' => [
                'url' => 'data:'.$o['mime'].';base64,'.base64_encode($o['bytes']),
            ]];
        }

        $lastError = 'LLM khong tra ve anh (model co the bi chan region).';
        foreach ($models as $model) {
            try {
                $res = Http::timeout($timeout)
                    ->withToken($key)
                    ->post($base.'/chat/completions', [
                        'model' => $model,
                        'messages' => [['role' => 'user', 'content' => $content]],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('LlmService tryon exception: '.$e->getMessage(), ['model' => $model]);
                $lastError = 'LLM tam thoi khong dung duoc.';

                continue;
            }

            $body = (string) $res->body();
            if (! $res->successful()) {
                $lastError = self::extractError($body) ?? 'LLM loi HTTP '.$res->status();
                Log::warning('LlmService tryon failed: HTTP '.$res->status(), ['model' => $model]);

                continue;
            }

            $parsed = self::extractText($body);
            $text = $parsed['text'] ?? '';
            if ($text === '' || ! preg_match('/data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)/', $text, $m)) {
                $lastError = 'LLM khong tra ve anh (model co the bi chan region).';

                continue;
            }

            $bytes = base64_decode($m[2], true);
            $mime = $bytes !== false ? self::sniffImageMime($bytes) : null;
            if ($bytes === false || $mime === null) {
                $lastError = 'LLM tra ve anh loi.';

                continue;
            }

            return ['bytes' => $bytes, 'mime' => $mime, 'model' => $parsed['model'] ?? $model];
        }

        throw new \RuntimeException($lastError);
    }

    private static function sniffImageMime(string $bytes): ?string
    {
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($bytes, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }
        if (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }

    /**
     * Doc anh qua 9router chat/completions (image_url data URL).
     * Model mac dinh ag/gemini-3.8-flash-low (doc anh that, da verify live).
     * Luu y: ag/gemini-3.1-flash-image MU anh (tra loi lung tung) nen
     * khong dung lam vision.
     *
     * @return array{text: string, model: string}
     */
    public static function describeImage(string $bytes, string $mime, ?string $prompt = null): array
    {
        $base = rtrim(config('services.llm.base_url'), '/');
        $key = config('services.llm.api_key');
        $models = self::modelList('vision_model', 'ag/gemini-3.8-flash-low');
        $timeout = max(30, (int) config('services.llm.timeout', 60) * 2);

        if (! $key) {
            throw new \RuntimeException('LLM chua cau hinh (thieu LLM_API_KEY).');
        }

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Anh phai la JPEG, PNG hoac WebP.');
        }

        $question = $prompt !== null && trim($prompt) !== ''
            ? mb_substr(trim($prompt), 0, 500)
            : 'Mo ta buc anh nay bang tieng Viet, ngan gon 2-3 cau: co gi trong anh, mau sac noi bat.';

        $lastError = 'LLM tra ve rong.';
        foreach ($models as $model) {
            try {
                $payload = [
                    'model' => $model,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $question],
                            ['type' => 'image_url', 'image_url' => [
                                'url' => 'data:'.$mime.';base64,'.base64_encode($bytes),
                            ]],
                        ],
                    ]],
                ];
                if ($maxTokens = config('services.llm.max_tokens')) {
                    $payload['max_tokens'] = (int) $maxTokens;
                }
                $res = Http::timeout($timeout)
                    ->withToken($key)
                    ->post($base.'/chat/completions', $payload);
            } catch (\Throwable $e) {
                Log::warning('LlmService vision exception: '.$e->getMessage(), ['model' => $model]);
                $lastError = 'LLM tam thoi khong dung duoc.';

                continue;
            }

            $body = (string) $res->body();

            if (! $res->successful()) {
                $lastError = self::extractError($body) ?? 'LLM loi HTTP '.$res->status();
                Log::warning('LlmService vision failed: HTTP '.$res->status(), ['model' => $model]);

                continue;
            }

            $parsed = self::extractText($body);
            if ($parsed === null) {
                $lastError = 'LLM tra ve rong.';

                continue;
            }

            return ['text' => $parsed['text'], 'model' => $parsed['model'] ?? $model];
        }

        throw new \RuntimeException($lastError);
    }

    /** Tool duy nhat cua tro ly ban hang: tim hang that trong catalog. */
    private static function searchTools(): array
    {
        return [[
            'type' => 'function',
            'function' => [
                'name' => 'search_products',
                'description' => 'Tim san pham THAT dang ban trong catalog Wibu Store. '
                    .'Bat buoc goi truoc khi goi y, bao gia hay noi ve ton kho. '
                    .'Chi duoc goi y san pham co trong ket qua tra ve.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => ['type' => 'string', 'description' => 'Tu khoa ten san pham / nhan vat / anime (VD gojo, luffy, nendoroid)'],
                        'max_price' => ['type' => 'integer', 'description' => 'Gia tran VND (VD duoi 1 trieu = 1000000)'],
                        'min_price' => ['type' => 'integer', 'description' => 'Gia san VND'],
                        'series' => ['type' => 'string', 'description' => 'Slug anime series (VD jujutsu-kaisen)'],
                        'category' => ['type' => 'string', 'description' => 'Slug danh muc'],
                        'limit' => ['type' => 'integer', 'description' => 'So san pham, mac dinh 5, toi da 8'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ]];
    }

    /** Tach tu khoa tieng Viet: bo stopword, giu tu dai de match OR. */
    private static function keywords(string $q): array
    {
        static $stop = null;
        $stop ??= array_flip(explode(' ', 'va cua la co trong mot nhung cac voi cho tu den nhu nay kia do day rat cung duoc bi de khi ma thi la o tai ve len xuong ra vao buc anh nay mot chiec cai nguoi mac tren duoi giua nen truoc sau ben canh nhau kha gi tu van tim kiem xem nao shop bao nhieu tien duoi tren khoang tu toi voi'));
        $words = preg_split('/\s+/u', mb_strtolower(trim($q))) ?: [];
        $out = [];
        foreach ($words as $w) {
            $w = trim((string) preg_replace('/[^\p{L}\p{N}]/u', '', $w));
            if (mb_strlen($w) > 2 && ! isset($stop[$w])) {
                $out[] = $w;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Query catalog that, tra ve ['items' => [...], 'note' => ?string].
     * Tu nhat (loc day du) -> rong dan (bo tu khoa -> bo loc gia, lay re nhat).
     */
    public static function searchProducts(array $args): array
    {
        $with = [
            'series', 'manufacturer', 'activeVariants.inventoryLevels',
            'activeVariants.openPreorderCampaigns', 'primaryMedia.asset', 'media.asset',
        ];
        $limit = min(8, max(1, (int) ($args['limit'] ?? 5)));
        $base = function () use ($with) {
            return Product::published()->with($with);
        };
        $apply = function ($q, array $a, bool $withKeywords) {
            if ($withKeywords && ! empty($a['q']) && ($keys = self::keywords((string) $a['q'])) !== []) {
                $q->where(function ($w) use ($keys) {
                    foreach ($keys as $k) {
                        $w->orWhere('name', 'ilike', '%'.$k.'%');
                    }
                });
            }
            if (! empty($a['series'])) {
                $q->whereHas('series', fn ($w) => $w->where('slug', (string) $a['series']));
            }
            if (! empty($a['manufacturer'])) {
                $q->whereHas('manufacturer', fn ($w) => $w->where('slug', (string) $a['manufacturer']));
            }
            if (! empty($a['category'])) {
                $q->whereHas('categories', fn ($w) => $w->where('slug', (string) $a['category']));
            }
            if (isset($a['max_price']) && is_numeric($a['max_price'])) {
                $q->whereHas('activeVariants', fn ($w) => $w->where('price_amount', '<=', (int) $a['max_price']));
            }
            if (isset($a['min_price']) && is_numeric($a['min_price'])) {
                $q->whereHas('activeVariants', fn ($w) => $w->where('price_amount', '>=', (int) $a['min_price']));
            }

            return $q;
        };

        $note = null;
        $items = $apply($base(), $args, true)->orderByDesc('published_at')->limit($limit)->get();
        if ($items->isEmpty() && ! empty($args['q'])) {
            $items = $apply($base(), $args, false)->orderByDesc('published_at')->limit($limit)->get();
        }
        if ($items->isEmpty() && (isset($args['max_price']) || isset($args['min_price']))) {
            $relaxed = $args;
            unset($relaxed['max_price'], $relaxed['min_price']);
            $items = $apply($base(), $relaxed, false)
                ->orderByRaw("(SELECT MIN(price_amount) FROM product_variants WHERE product_id = products.id AND status = 'active') ASC")
                ->limit($limit)->get();
            if ($items->isNotEmpty()) {
                $note = 'Khong co san pham dung ngan sach, day la cac mon re nhat shop (gia that).';
            }
        }

        return [
            'note' => $note,
            'items' => $items->map(function (Product $p) {
                $imgs = collect([$p->primaryMedia?->asset?->url()])
                    ->merge($p->media->map(fn ($m) => $m->asset?->url()))
                    ->filter()->unique()->take(3)->values()->all();

                return [
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'price_amount' => $p->activeVariants->min('price_amount'),
                    'currency' => 'VND',
                    'series' => $p->series?->only(['name', 'slug']),
                    'image' => $imgs[0] ?? null,
                    'images' => $imgs,
                    'availability' => $p->availability(),
                    'url' => '/products/'.$p->slug,
                ];
            })->all(),
        ];
    }

    /** Parse 1 chat response (JSON hoac SSE) thanh content + tool_calls. */
    private static function parseMessage(string $body): ?array
    {
        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '{')) {
            $jsonPart = preg_replace('/data:\s*\[DONE\]\s*$/', '', $trimmed);
            $decoded = json_decode($jsonPart, true);
            if (! is_array($decoded)) {
                return null;
            }
            $message = $decoded['choices'][0]['message'] ?? [];
            $content = $message['content'] ?? null;
            $toolCalls = $message['tool_calls'] ?? [];
            if ((! is_string($content) || trim($content) === '') && $toolCalls === []) {
                return null;
            }

            return [
                'content' => is_string($content) ? trim($content) : null,
                'tool_calls' => is_array($toolCalls) ? array_values($toolCalls) : [],
                'model' => $decoded['model'] ?? null,
            ];
        }

        // SSE: gop content + tool_calls (arguments ve theo tung manh, gop theo index).
        $text = '';
        $model = null;
        $calls = [];
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, 'data:')) {
                continue;
            }
            $payload = trim(substr($line, 5));
            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }
            $chunk = json_decode($payload, true);
            if (! is_array($chunk)) {
                continue;
            }
            $model ??= $chunk['model'] ?? null;
            $delta = $chunk['choices'][0]['delta'] ?? [];
            if (isset($delta['content']) && is_string($delta['content'])) {
                $text .= $delta['content'];
            }
            foreach ((array) ($delta['tool_calls'] ?? []) as $tc) {
                $i = (int) ($tc['index'] ?? 0);
                $calls[$i]['id'] ??= $tc['id'] ?? 'call_'.$i;
                $calls[$i]['type'] ??= 'function';
                if (isset($tc['function']['name'])) {
                    $calls[$i]['function']['name'] = $tc['function']['name'];
                }
                if (isset($tc['function']['arguments'])) {
                    $calls[$i]['function']['arguments'] = ($calls[$i]['function']['arguments'] ?? '').$tc['function']['arguments'];
                }
            }
        }
        ksort($calls);
        $toolCalls = array_values(array_filter($calls, fn ($c) => isset($c['function']['name'])));
        if (trim($text) === '' && $toolCalls === []) {
            return null;
        }

        return ['content' => trim($text) !== '' ? trim($text) : null, 'tool_calls' => $toolCalls, 'model' => $model];
    }

    /** 1 luot goi chat/completions, co the kem tools. */
    private static function postChatMessages(array $messages, array $tools = []): array
    {
        $base = rtrim(config('services.llm.base_url'), '/');
        $key = config('services.llm.api_key');
        $model = config('services.llm.chat_model', 'ag/gemini-3.8-flash-low');
        $timeout = max(10, (int) config('services.llm.timeout', 60));

        if (! $key) {
            throw new \RuntimeException('LLM chua cau hinh (thieu LLM_API_KEY).');
        }

        $payload = ['model' => $model, 'messages' => $messages, 'stream' => false];
        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }
        if ($maxTokens = config('services.llm.max_tokens')) {
            $payload['max_tokens'] = (int) $maxTokens;
        }

        try {
            $res = Http::timeout($timeout)->withToken($key)->post($base.'/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('LlmService exception: '.$e->getMessage(), ['model' => $model]);

            throw new \RuntimeException('LLM tam thoi khong dung duoc.');
        }

        $body = (string) $res->body();
        if (! $res->successful()) {
            $msg = self::extractError($body) ?? 'LLM loi HTTP '.$res->status();
            // Model khong ho tro tools -> thu lai khong kem tools.
            if ($tools !== [] && $res->status() === 400 && stripos($body, 'tool') !== false) {
                Log::warning('LlmService tools unsupported, retry plain.', ['model' => $model]);

                return self::postChatMessages($messages);
            }
            Log::warning('LlmService failed: HTTP '.$res->status(), ['model' => $model]);

            throw new \RuntimeException($msg);
        }

        $parsed = self::parseMessage($body);
        if ($parsed === null) {
            throw new \RuntimeException('LLM tra ve rong.');
        }

        return ['content' => $parsed['content'], 'tool_calls' => $parsed['tool_calls'], 'model' => $parsed['model'] ?? $model];
    }

    /**
     * Tro ly ban hang co tool calling that (toi da 2 vong goi tool).
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{text: string, model: string, products: array}
     */
    public static function shopAssistant(string $message, array $history = []): array
    {
        // Giu giong de thuong nhung tranh tu nhay cam voi Google filter (waifu/senpai).
        $system = 'Ban la tro ly anime cua Wibu Store: shop figure + cosplay anime. '
            .'Noi tieng Viet, vui ve, than thien, de thuong, goi khach la ban. '
            .'Khi khach hoi goi y, gia, ton kho hay "con hang khong", BAT BUOC goi search_products truoc. '
            .'Chi goi y san pham co trong ket qua tool, kem link markdown [Ten](/products/slug) + gia VND. '
            .'Khong duoc bia ten, gia hay link. Ket qua tool co URL anh that (image/images); '
            .'khi khach muon xem hinh thi chen anh bang ![Ten](url_anh). '
            .'Tra loi dung markdown (bold, list). Tien la VND (integer).';

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($history, -20) as $h) {
            $text = trim((string) ($h['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $messages[] = [
                'role' => ($h['role'] ?? '') === 'user' ? 'user' : 'assistant',
                'content' => mb_substr($text, 0, 1000),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => mb_substr($message, 0, 1000)];

        $collected = [];
        $answer = null;
        for ($round = 0; $round < 2; $round++) {
            $out = self::postChatMessages($messages, self::searchTools());
            $answer = $out;
            if ($out['tool_calls'] === []) {
                break;
            }
            $assistantMsg = ['role' => 'assistant', 'content' => $out['content'], 'tool_calls' => $out['tool_calls']];
            $messages[] = $assistantMsg;
            foreach ($out['tool_calls'] as $i => $call) {
                $name = $call['function']['name'] ?? '';
                if ($name === 'search_products') {
                    $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);
                    $res = self::searchProducts(is_array($args) ? $args : []);
                    foreach ($res['items'] as $p) {
                        $collected[$p['slug']] = $p;
                    }
                    $result = ['products' => $res['items']];
                    if ($res['note'] !== null) {
                        $result['note'] = $res['note'];
                    }
                } else {
                    $result = ['error' => 'Unknown tool: '.$name];
                }
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? 'call_'.$i,
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        // Het vong tool ma chua co text -> chot 1 cau khong tool.
        if ($answer === null || ($answer['content'] === null && $answer['tool_calls'] !== [])) {
            $answer = self::postChatMessages($messages);
        }

        return [
            'text' => $answer['content'] ?? 'Minh tim giup ban ngay nhe, ban mo ta them nhan vat hoac ngan sach giup minh.',
            'model' => $answer['model'],
            'products' => array_values($collected),
        ];
    }
}
