# Knowledge Base: Revert Sidebar Search + Command Palette Content Search

**Date:** 2026-06-12
**Status:** Approved

## Problem

The flat snippet result list (shipped in v2.2.0 / PR #10) replaced the sidebar trees
while searching. The user prefers the original sidebar behavior (trees filtered by
title/filename, auto-opening). Content search should instead live in a dedicated
"advanced search" overlay using the TallStackUI command palette (v3.1.1, available
in the installed version).

## Scope

- Revert the sidebar search UI to the pre-#10 behavior.
- Keep the content-search backend (`SearchSnippet`, `KnowledgeManager::searchDocs`,
  `getDocPlainText`) — it now powers the palette.
- Add a command palette scoped to the Knowledge page (not app-global).
- Heading anchors (#11) and the native lightbox stay untouched.

## Design

### 1. Sidebar revert

`Knowledge.php`:
- Remove `$searchResults` and `searchArticles()`.
- `updatedSearch()` again calls `loadCategories()` + `loadPackageDocs()`.
- Restore the three `->when($this->search, …title LIKE…)` filters in
  `loadCategories()`, the search filtering in `loadPackageDocs()`, and
  `filterDocsTree()`.

`knowledge.blade.php` / `knowledge-item.blade.php`:
- Remove the flat search result list; trees render unconditionally again.
- Restore the auto-open-on-search Alpine watchers and the `isSearching` prop.

### 2. `KnowledgeSearch` support class

`src/Support/KnowledgeSearch.php` with
`search(string $term, ?Authenticatable $user): array` — merges:
- Article hits (moved from the Livewire component): `title`/`content`/attribute
  translation LIKE matches, constrained by `is_published` + `visibleToUser`,
  mapped to `{type: 'article', id, title, breadcrumb, snippet}`.
- Package doc hits via `KnowledgeManager::searchDocs($term, $user)`.

### 3. Palette endpoint

`GET /knowledge/palette-search` (name `knowledge.palette-search`) in the package
routes, middleware `web, auth:web, permission` (same group as the page route).
The TallStackUI palette sends the term as `?search=`. Behavior:
- Empty/missing `search` → empty JSON array.
- Otherwise maps `KnowledgeSearch::search()` results to palette options:
  - `label`: title
  - `description`: `breadcrumb — snippet` as plain text (palette renders
    description with `x-text`, so `<mark>` is stripped and entities decoded)
  - `value`: `article:<id>` or `doc:<package>:<path>` (required unique value)
  - extra fields `type`, `id`, `package`, `path` for the select handler

### 4. Palette component on the Knowledge page

```blade
<x-command-palette
    id="knowledge-search"
    :request="route('knowledge.palette-search')"
    select="label:label|value:value|description:description"
    x-on:select="$event.detail.type === 'article'
        ? $wire.selectArticle($event.detail.id)
        : $wire.selectPackageDoc($event.detail.package, $event.detail.path)"
/>
```

- Default shortcut (Ctrl+K) applies.
- A small button next to the sidebar search input opens the palette via
  `$tsui.open.commandPalette('knowledge-search')` as the discoverable entry point.
- Selection loads the article/doc via the existing `$wire` methods; the
  `knowledge-content-loaded` scroll behavior from #11 keeps working.

## Testing

1. Feature (endpoint): returns article + doc options with plain-text description;
   excludes unpublished/invisible articles and invisible packages; empty term →
   empty array; unauthenticated → redirect/401.
2. Livewire: sidebar search filters trees by title again (replaces the removed
   `searchResults` tests).
3. Unit: `SearchSnippet`, `searchDocs`, `HeadingAnchors` tests unchanged.
