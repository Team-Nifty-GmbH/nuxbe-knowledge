# Knowledge Base: Heading Anchor Links

**Date:** 2026-06-12
**Status:** Approved (design discussed 2026-06-11)

## Problem

There is no way to link to a section inside a knowledge article or package doc.
Article/doc selection is already URL-addressable (`#[Url]` properties), but rendered
headings carry no `id` attributes, so URL fragments go nowhere — and even with ids the
browser cannot scroll after Livewire swaps content client-side.

## Scope

- In scope: slug ids on headings (articles + package docs), hover anchor link per
  heading that sets the URL fragment and copies the link, scrolling to the fragment
  after content loads (initial load and SPA-internal navigation), fragments on
  internal `.md` doc links.
- Out of scope: editor-defined custom anchors, anchors in version
  comparison/history views.

## Design

### `HeadingAnchors` helper (`src/Support/HeadingAnchors.php`)

`HeadingAnchors::apply(string $html): string`:

- Regexes `<h1>`–`<h6>` elements; for each, derives `Str::slug(strip_tags($inner))`.
- Skips headings that already have an `id` attribute; skips empty slugs.
- Deduplicates repeated slugs by suffixing `-2`, `-3`, ….
- Injects `id="<slug>"` and appends
  `<a href="#<slug>" class="heading-anchor" data-heading-anchor>#</a>` inside the
  heading.

### Rendering

- Package docs: `KnowledgeManager::renderDoc()` applies the helper as the last
  post-processing step (inside the existing cache closure; bump nothing — cache key
  already contains `filemtime`, stale cached HTML without ids simply expires; during
  rollout a stale entry only means anchors appear after cache TTL).
- Articles: the view applies `HeadingAnchors::apply($articleForm->content)` in the
  article display branch (content is stored unchanged in the DB).

### Frontend behavior (knowledge.blade.php)

- Content containers get Tailwind arbitrary-variant styling: anchors are invisible
  until heading hover (`[&_.heading-anchor]:opacity-0`,
  `[&_:is(h1,h2,h3,h4,h5,h6):hover_.heading-anchor]:opacity-100`, plus transition
  and color classes).
- Click delegation on the content container: a click on `a.heading-anchor` sets
  `location.hash` and copies `location.href` to the clipboard.
- `selectArticle()` and `selectPackageDoc()` dispatch a
  `knowledge-content-loaded` browser event; an Alpine `x-on:…​.window` listener
  scrolls the fragment target into view after `$nextTick`.
- Initial page load works natively: content is part of the server-rendered HTML, so
  the browser scrolls to `#fragment` itself.
- Doc-internal `.md` links already preserve fragments (`renderDoc` writes them into
  `href`); the existing click handler navigates via `selectPackageDoc`, after which
  the loaded-event scroll picks up the fragment.

## Testing

1. Unit: `HeadingAnchors::apply` — ids + anchor links added, existing ids untouched,
   duplicate slugs deduplicated, nested tags in headings stripped for slug, empty
   heading skipped.
2. Unit: `renderDoc` output contains heading ids + anchor links.
3. Livewire: article view HTML contains heading id and anchor link.
