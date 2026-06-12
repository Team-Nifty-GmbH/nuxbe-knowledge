# Knowledge Base: Content Search + Native Lightbox

**Date:** 2026-06-11
**Status:** Approved

## Problem

The knowledge base search only matches article titles (`title LIKE`) and package doc
file/directory names. Matches inside article content or markdown doc content are
invisible. Users cannot find articles by what they actually say.

Additionally, the package doc view ships its own Alpine lightbox even though Flux ERP
now provides a global lightbox component (`$nuxbe.openLightbox`) in the app layout.

## Scope

- In scope: content search for user articles and package docs, flat result list with
  snippets, replacing the custom lightbox with the native Flux one.
- Out of scope: heading anchor links (separate branch/PR), relevance ranking, search
  index tables, external search engines (Scout/Meilisearch).

## Decisions

1. **Result UI:** While the search field is non-empty, the sidebar replaces the
   category/doc trees with a flat result list: hit count, small breadcrumb
   (category path or package/folder path), title, and a text snippet with the search
   term highlighted via `<mark>`.
2. **Technique:** Live search without an index. The corpus is an internal knowledge
   base; search input is already debounced (300 ms). If it ever becomes slow, a
   fulltext index table is the upgrade path.
3. **Lightbox:** Use the global Flux lightbox; delete the local Alpine implementation.

## Design

### Data structure

`Knowledge` Livewire component gets a new `array $searchResults = []`, filled in
`updatedSearch()`. Each entry:

```php
[
    'type' => 'article' | 'doc',
    'id' => int|null,            // article
    'package' => string|null,    // doc
    'path' => string|null,       // doc relative path
    'title' => string,
    'breadcrumb' => string,      // e.g. "Buchhaltung › Mahnwesen" or "Flux ERP › Zahlungen"
    'snippet' => string,         // pre-escaped HTML containing <mark>
]
```

### Article search

Extend the existing article query (keeps `is_published` + `visibleToUser` constraints):

- `title LIKE %term%` OR `content LIKE %term%` OR a match in the attribute
  translations (model uses `HasAttributeTranslations`; `title`, `content` and
  `content_markdown` are translatable — the current search ignores translations
  entirely).
- Snippet built in PHP: `strip_tags()` of the content in the currently selected
  language (fallback: base content) → first occurrence via `mb_stripos` → ±60 chars
  context → `e()` everything → wrap the matched term in `<mark>`.
- Title-only matches use the start of the plain content as snippet. The same applies
  when the term only matches a translation in a language other than the currently
  selected one: the article is listed, the snippet shows the current language's
  content start without `<mark>`.

### Package doc search

New method `KnowledgeManager::searchDocs(string $term, ?Authenticatable $user): array`:

- Iterates the visible doc trees (`getAllVisibleDocsTrees`).
- Per `.md` file, plain text is produced by GFM-converting the markdown and applying
  `strip_tags`, cached with an mtime-based key analogous to the `renderDoc` cache.
- A file matches if its display name OR its plain text contains the term
  (case-insensitive). Snippet extraction shares the helper used for articles.

### Sidebar UI

In `knowledge.blade.php`, when `mb_strlen($search) > 0`:

- Hide the category trees, uncategorized list, and package doc trees.
- Render the flat result list; clicking a result calls `selectArticle(id)` or
  `selectPackageDoc(package, path)` and closes the sidebar on mobile.

Now obsolete and removed:

- The `title LIKE` conditions inside `loadCategories()`.
- `filterDocsTree()` and the search filtering in `loadPackageDocs()`.
- The auto-open-trees-on-search Alpine watchers (introduced in commit `5b43c99`) —
  trees are hidden during search, so they no longer need to open.

`<mark>` gets Tailwind styling consistent with light/dark mode.

### Lightbox

- Remove the local lightbox markup and the `lightboxSrc` Alpine state from the
  package doc view.
- The image click handler calls `$nuxbe.openLightbox($event.target.src)` instead.
  The lightbox component is globally included in the Flux app layout; no markup is
  needed in this package.

### Error handling

- Unreadable/missing doc files are skipped silently (consistent with current tree
  scanning).
- Snippets are fully escaped before inserting `<mark>`; no raw article HTML reaches
  the sidebar.

## Testing

Testbench/Pest tests in the package:

1. Article whose **content** (not title) contains the term appears in
   `searchResults` with a `<mark>` snippet.
2. Package doc whose markdown content contains the term appears in results.
3. Unpublished articles and articles not visible to the user are excluded.
4. Doc packages not visible to the user are excluded.
5. Clearing the search restores the tree view (empty `searchResults`).
