<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# ui

## Purpose
Generic, domain-agnostic primitives reused by every admin page: the status pill, the button, the labelled input and the server-side pager. These carry no business logic and know nothing about the API.

## Key Files
| File | Description |
|------|-------------|
| `Badge.astro` | Pill. Props `variant` (success/warning/danger/info/neutral/brand/yellow), `size` (sm/md), `class`. Label comes from the slot. |
| `Button.astro` | Renders an `<a>` when `href` is set, otherwise a `<button type>`. Props `variant` primary/secondary/danger/ghost/comic, `size` sm/md/lg, `href`, `type`, `id`, `disabled`, `class`. |
| `Input.astro` | Labelled input. Props `label`, `name`, `id` (defaults to `name`), `type`, `value`, `placeholder`, `required`, `disabled`, `error`, `hint`, `class`; extra attrs are spread onto the `<input>`. |
| `Pagination.astro` | Server-side pager. Props `meta` (`PageMeta`), `pathname`, `params`. Renders the from/to/total range plus Trước / current / Sau links. |

## For AI Agents

### Working In This Directory
- The extra-class prop is named `class`, not `className` (the storefront uses `className`). Do not mix them up.
- `Button.astro` picks `<a>` vs `<button>` from `href`, not from the caller. `type` and `disabled` only apply to the `<button>` branch — a `disabled` button with an `href` still navigates.
- `Input.astro` renders a red `*` when `required`; `error` replaces `hint` in the output. It has no `select`, `textarea` or checkbox variant — those are hand-rolled in the pages.
- `Input.astro` spreads `...rest` onto the `<input>`, so `maxlength`, `min`, `max`, `pattern`, `autocomplete` pass straight through.
- `Pagination.astro` only renders when `lastPage > 1 || total > 0`. It has no numbered/first/last links — only prev/current/next.
- `Pagination.astro` does not read `?page`; the page must supply `meta` from `apiGet` and pass `params` for the filters to survive paging. Params it does not receive are dropped from the links.
- `Badge.astro` is the generic pill. Domain statuses must go through `../domain/*Badge.astro` instead — those wrap this component and own the status→label/colour map.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Visual only — check an affected route in `pnpm dev` (:4322).
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `PageMeta` type consumed by `Pagination.astro`
- `../domain/*Badge.astro` — wraps `Badge.astro` for every status display

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
