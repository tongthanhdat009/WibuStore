# Ai

**Parent context:** `../AGENTS.md`
**Generated:** 2026-10-30 · **Updated:** 2026-10-30

## Purpose
Single LLM entrypoint: `LlmService` — OpenAI-compatible chat proxy (9router default) + tool-grounded sales assistant + image generate/describe. Key stays server-side (`config/services.php` `llm`, env `LLM_API_KEY`).

## Key Files
| File | Description |
|------|-------------|
| `LlmService.php` | `generate()` (chat, SSE-or-JSON tolerant), `shopAssistant()` (2-round `search_products` tool loop), `generateImage()`/`describeImage()` (model-list fallback), `searchProducts()` (strict→relaxed catalog query) |

## For AI Agents

### Working In This Directory
- Never send `stream:false` — 9router answers SSE (`data:` chunks) even without it; `extractText`/`parseMessage` handle both. `ag/gemini-3.1-flash-image` is image-only, never vision (returns garbage); vision default `ag/gemini-3.8-flash-low`.
- `search_products` is the ONLY sales tool; suggestions must come from its results (name/slug/price/link/image), never invented. Budget: history last 20, prompt 1000 chars, product limit 8.
- Tool-less retry: `postChatMessages` retries without tools on HTTP 400 mentioning `tool` (model lacks tool support).
- Money = integer VND; errors are `RuntimeException` with Vietnamese message, mapped by callers.

### Testing Requirements
- `make test` from repo root (DB `wibu_store_test`); suite `Feature/AiTest.php`.

## Dependencies

### Internal
- `App\Models\Product` (+ `activeVariants`, `series`, `media`) — the only grounded catalog source

### External
- 9router OpenAI-compatible `/chat/completions` + `/images/generations` (`LLM_BASE_URL`, `LLM_CHAT_MODEL`, `LLM_IMAGE_MODEL`, `LLM_VISION_MODEL`, `LLM_MAX_TOKENS`, `LLM_TIMEOUT_SECONDS`)

## Manual Notes
