<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# comic

## Purpose
Comic-book / pop-art editorial blocks for the storefront: the cover hero, category panels, the collection banner, series cards, sticker badges, issue counters, and the scroll-driven video story.

## Key Files
| File | Description |
|------|-------------|
| `ComicCoverHero.astro` | Full-bleed hero carousel over `HeroCampaign[]`. Prev/next controls and slide index use the ids `hero-btn-prev`, `hero-btn-next`, `hero-current-index`, `hero-issue-label` |
| `VideoStory.astro` | Scroll-scrubbed video story — 3 clips from `public/videos/`, luma-keyed onto a canvas, with chapter copy, dots and a progress bar |
| `CategoryComicCard.astro` | Category panel link, background tinted from `category.accentColor`; `isWide` spans two columns |
| `FeaturedCollectionBanner.astro` | Collection promo banner — halftone background, badge, tagline, description, and the collection's featured products |
| `AnimeSeriesCard.astro` | Poster card for an `AnimeSeries` linking to `/anime/{slug}`, with figure-count badge and title overlay |
| `StickerBadge.astro` | Rotated comic sticker label. `variant` yellow/brand/mint/blue/pink/white, `angle` left/right/none, `size` sm/md/lg |
| `IssueCounter.astro` | Small "01 / 04" counter chip — props `current`, `total` (default `'04'`), `label` |

## For AI Agents

### Working In This Directory
- `StickerBadge.astro` is the canonical badge for this family; use it instead of hand-rolling sticker markup.
- `VideoStory.astro` is fully self-contained: it declares its own `chapters` array inline (`src`, `tag`, `title`, `subtitle`, `body`), renders one `<video>` per chapter, and keys the footage out on a canvas (near-white pixels become transparent). Chapter count is hardcoded to 3 in the markup (the `/ 03` counter and the `chapters.map` blocks) — adding a fourth clip means updating the counter text as well.
- Video sources are absolute public paths (`/videos/ichigo.mp4`, `/videos/goku.mp4`, `/videos/promo.mp4`); the files live in `frontend/public/videos/`. There is no CDN, manifest or upload path.
- `VideoStory` binds itself through `initVideoStory()` guarded by `wrap.dataset.bound` and re-binds on `astro:page-load`. Do not call it manually from another script.
- `VideoStory` respects `prefers-reduced-motion` (scroll scrubbing is disabled) and uses a 420vh wrapper with a `sticky` viewport.
- `ComicCoverHero.astro` expects a non-empty `campaigns` array — it dereferences `campaigns[0].issueNumber` during render and will throw on an empty array.
- Props are typed in frontmatter: `ComicCoverHero` takes `HeroCampaign[]`, `FeaturedCollectionBanner` takes a `Collection` plus its `Product[]`, `CategoryComicCard` takes a `CategoryInfo` plus optional `isWide`.

### Testing Requirements
- `pnpm build` in `frontend/`.
- `VideoStory` needs a real browser (canvas + `requestAnimationFrame`): open `/` in `pnpm dev` and confirm the clip swaps and the chapter copy changes while scrolling.
- No test runner is configured.

### Common Patterns
- Money in this family comes from `lib/utils.ts` → `formatVND`.
- Style with Tailwind 4 utilities and the `@theme` tokens; the `.comic-*`, `.pattern-halftone`, `.speed-lines` and `.sticker-pop` utilities are defined in `src/styles/global.css`.
- Reuse `../ui/Button.astro` for CTAs instead of hand-building anchors.

## Dependencies

### Internal
- `../ui/Button.astro` — CTAs in `VideoStory`, `ComicCoverHero`, `FeaturedCollectionBanner`
- `../../lib/utils.ts` — `formatVND`
- `../../types/index.ts` — `HeroCampaign`, `Collection`, `AnimeSeries`, `CategoryInfo`, `Product`
- `../../public/videos/*.mp4` — the footage `VideoStory` plays

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`, browser Canvas 2D API (no library)

<!-- MANUAL: -->
