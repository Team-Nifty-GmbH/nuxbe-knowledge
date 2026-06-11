# Knowledge Content Search + Native Lightbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Search matches article and package-doc *content* (not just titles/filenames) and shows a flat result list with highlighted snippets; the custom Alpine lightbox is replaced by the global Flux lightbox.

**Architecture:** A small `SearchSnippet` helper builds escaped `<mark>` snippets from plain text. `KnowledgeManager` gains an mtime-cached plaintext extractor and `searchDocs()`. The `Knowledge` Livewire component fills `$searchResults` in `updatedSearch()`; the Blade sidebar swaps trees for a flat result list while searching. Tree-filtering code becomes dead and is removed.

**Tech Stack:** Laravel/FluxErp package, Livewire 3, Pest + Orchestra Testbench, league/commonmark, TallStackUI.

**Spec:** `docs/superpowers/specs/2026-06-11-knowledge-content-search-design.md`

**Working directory:** `/Users/patrickweh/Projects/team-nifty/nuxbe/packages/packages/nuxbe-knowledge` (branch `feature/knowledge-content-search`)

**Test runner:** `./vendor/bin/testbench package:test --testsuite Unit` (suites: `Unit`, `Feature`, `Livewire`). Single file: append ` -- --filter='test name'` style filtering via `--filter`.

---

### Task 1: `SearchSnippet` helper

**Files:**
- Create: `src/Support/SearchSnippet.php`
- Test: `tests/Unit/SearchSnippetTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

use TeamNiftyGmbH\NuxbeKnowledge\Support\SearchSnippet;

test('make returns null when term not found', function (): void {
    expect(SearchSnippet::make('some plain text', 'missing'))->toBeNull();
});

test('make wraps the matched term in mark preserving original casing', function (): void {
    $snippet = SearchSnippet::make('Overdue Invoices are processed automatically.', 'invoices');

    expect($snippet)->toContain('<mark>Invoices</mark>');
});

test('make adds ellipsis when text is truncated', function (): void {
    $before = str_repeat('a ', 100);
    $after = str_repeat('b ', 100);
    $snippet = SearchSnippet::make($before.'needle'.$after, 'needle');

    expect($snippet)->toStartWith('…')
        ->toEndWith('…')
        ->toContain('<mark>needle</mark>');
});

test('make escapes html around the match', function (): void {
    $snippet = SearchSnippet::make('foo <script>alert(1)</script> needle bar', 'needle');

    expect($snippet)->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

test('make collapses whitespace', function (): void {
    $snippet = SearchSnippet::make("line one\n\n   needle\t end", 'needle');

    expect($snippet)->toContain('line one <mark>needle</mark> end');
});

test('fallback returns escaped truncated text start', function (): void {
    $text = str_repeat('word ', 50);

    $fallback = SearchSnippet::fallback($text);

    expect(mb_strlen(strip_tags($fallback)))->toBeLessThanOrEqual(121)
        ->and($fallback)->toEndWith('…');
});

test('fallback returns full short text without ellipsis', function (): void {
    expect(SearchSnippet::fallback('short text'))->toBe('short text');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/testbench package:test --testsuite Unit --filter SearchSnippet`
Expected: FAIL — `Class "TeamNiftyGmbH\NuxbeKnowledge\Support\SearchSnippet" not found`

- [ ] **Step 3: Implement `SearchSnippet`**

```php
<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\Support;

class SearchSnippet
{
    public static function make(string $plainText, string $term, int $context = 60): ?string
    {
        $plainText = trim(preg_replace('/\s+/u', ' ', $plainText));
        $position = mb_stripos($plainText, $term);

        if ($position === false || mb_strlen($term) === 0) {
            return null;
        }

        $start = max(0, $position - $context);
        $end = min(mb_strlen($plainText), $position + mb_strlen($term) + $context);

        $before = mb_substr($plainText, $start, $position - $start);
        $match = mb_substr($plainText, $position, mb_strlen($term));
        $after = mb_substr($plainText, $position + mb_strlen($term), $end - $position - mb_strlen($term));

        return ($start > 0 ? '…' : '')
            .e($before)
            .'<mark>'.e($match).'</mark>'
            .e($after)
            .($end < mb_strlen($plainText) ? '…' : '');
    }

    public static function fallback(string $plainText, int $length = 120): string
    {
        $plainText = trim(preg_replace('/\s+/u', ' ', $plainText));

        if (mb_strlen($plainText) <= $length) {
            return e($plainText);
        }

        return e(mb_substr($plainText, 0, $length)).'…';
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/testbench package:test --testsuite Unit --filter SearchSnippet`
Expected: PASS (7 tests)

- [ ] **Step 5: Commit**

```bash
git add src/Support/SearchSnippet.php tests/Unit/SearchSnippetTest.php
git commit -m "feat: add SearchSnippet helper for highlighted search excerpts"
```

---

### Task 2: `KnowledgeManager::searchDocs()` + cached plaintext

**Files:**
- Modify: `src/Support/KnowledgeManager.php` (add 3 methods, nothing removed)
- Create: `tests/fixtures/docs/release-notes.md`
- Test: `tests/Unit/KnowledgeManagerTest.php` (append tests)

Fixture content (already exists): `tests/fixtures/docs/getting-started.md` contains "This is a test doc.", `tests/fixtures/docs/guides/advanced.md` contains "Advanced content here.". Both contain their own display name as a markdown heading, so they cannot test a *name-only* match — that's what the new fixture is for.

- [ ] **Step 0: Create name-only-match fixture**

`tests/fixtures/docs/release-notes.md` (display name "Release Notes" must NOT appear in the body):

```markdown
# Changelog

Version 1.0 shipped.
```

Existing tree tests only assert non-empty trees and pick the first file, so the extra fixture is safe.

- [ ] **Step 1: Write the failing tests** (append to `tests/Unit/KnowledgeManagerTest.php`)

```php
test('searchDocs finds files by content', function (): void {
    $manager = new KnowledgeManager;
    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $results = $manager->searchDocs('content here', null);

    expect($results)->toHaveCount(1)
        ->and($results[0]['type'])->toBe('doc')
        ->and($results[0]['package'])->toBe('test-package')
        ->and($results[0]['path'])->toBe('guides/advanced.md')
        ->and($results[0]['title'])->toBe('Advanced')
        ->and($results[0]['breadcrumb'])->toBe('Test Docs › Guides')
        ->and($results[0]['snippet'])->toContain('<mark>content here</mark>');
});

test('searchDocs finds files by name with fallback snippet', function (): void {
    $manager = new KnowledgeManager;
    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $results = $manager->searchDocs('release notes', null);

    expect($results)->toHaveCount(1)
        ->and($results[0]['path'])->toBe('release-notes.md')
        ->and($results[0]['snippet'])->not->toContain('<mark>')
        ->and($results[0]['snippet'])->toContain('Version 1.0 shipped.');
});

test('searchDocs returns empty for invisible packages', function (): void {
    $manager = new KnowledgeManager;
    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
        roles: ['Some Role'],
    );

    expect($manager->searchDocs('content here', null))->toBeEmpty();
});
```

Note: file titles come from `formatName()` — `advanced.md` → `Advanced`, `release-notes.md` → `Release Notes`, numeric prefixes stripped. `searchDocs('release notes', null)` matches the *name* case-insensitively but not the body; snippet falls back without `<mark>`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/testbench package:test --testsuite Unit --filter searchDocs`
Expected: FAIL — `Call to undefined method ...KnowledgeManager::searchDocs()`

- [ ] **Step 3: Implement** (add to `KnowledgeManager`, after `renderDoc()`; add `use TeamNiftyGmbH\NuxbeKnowledge\Support\SearchSnippet;` is not needed — same namespace)

```php
    public function searchDocs(string $term, ?Authenticatable $user): array
    {
        $results = [];

        foreach ($this->getAllVisibleDocsTrees($user) as $package => $config) {
            $this->searchDocsTree($package, $config['label'], $config['tree'], $term, [], $results);
        }

        return $results;
    }

    public function getDocPlainText(string $package, string $relativePath): ?string
    {
        $path = $this->resolveLanguagePath($package);

        if (! $path) {
            return null;
        }

        $fullPath = $path.'/'.ltrim($relativePath, '/');

        if (! file_exists($fullPath) || ! str_ends_with($fullPath, '.md')) {
            return null;
        }

        $languageCode = $this->resolveLanguageCode();
        $cacheKey = "knowledge.docs.plain.{$package}.{$languageCode}.".md5($relativePath).'.'.filemtime($fullPath);

        return Cache::remember($cacheKey, 3600, function () use ($fullPath): string {
            $markdown = Blade::render(file_get_contents($fullPath));
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            return strip_tags($converter->convert($markdown)->getContent());
        });
    }

    protected function searchDocsTree(
        string $package,
        string $label,
        array $items,
        string $term,
        array $breadcrumb,
        array &$results,
    ): void {
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'directory') {
                $this->searchDocsTree(
                    $package,
                    $label,
                    $item['children'] ?? [],
                    $term,
                    array_merge($breadcrumb, [$item['name']]),
                    $results,
                );

                continue;
            }

            $plainText = $this->getDocPlainText($package, $item['relative_path']) ?? '';
            $snippet = SearchSnippet::make($plainText, $term);

            if (is_null($snippet) && mb_stripos($item['name'], $term) === false) {
                continue;
            }

            $results[] = [
                'type' => 'doc',
                'package' => $package,
                'path' => $item['relative_path'],
                'title' => $item['name'],
                'breadcrumb' => implode(' › ', array_merge([$label], $breadcrumb)),
                'snippet' => $snippet ?? SearchSnippet::fallback($plainText),
            ];
        }
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/testbench package:test --testsuite Unit`
Expected: PASS (all Unit tests — confirms nothing existing broke)

- [ ] **Step 5: Commit**

```bash
git add src/Support/KnowledgeManager.php tests/Unit/KnowledgeManagerTest.php tests/fixtures/docs/release-notes.md
git commit -m "feat: add content search across package docs with cached plaintext"
```

---

### Task 3: Article search + `$searchResults` in the Livewire component

**Files:**
- Modify: `src/Livewire/Knowledge.php`
- Test: `tests/Livewire/KnowledgeSearchTest.php` (new)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('search matches article content and builds a marked snippet', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Dunning',
        'content' => '<h2>Levels</h2><p>Overdue invoices are processed automatically.</p>',
        'is_published' => true,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'overdue invoices');

    $results = $component->get('searchResults');

    expect($results)->toHaveCount(1)
        ->and($results[0]['type'])->toBe('article')
        ->and($results[0]['title'])->toBe('Dunning')
        ->and($results[0]['snippet'])->toContain('<mark>Overdue invoices</mark>')
        ->and($results[0]['snippet'])->not->toContain('<p>');
});

test('search matches article title with fallback snippet', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Payment Runs',
        'content' => '<p>Some unrelated body text.</p>',
        'is_published' => true,
    ]);

    $results = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'payment runs')
        ->get('searchResults');

    expect($results)->toHaveCount(1)
        ->and($results[0]['snippet'])->toContain('Some unrelated body text.');
});

test('search excludes unpublished articles', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Draft',
        'content' => '<p>secret draft content</p>',
        'is_published' => false,
    ]);

    $results = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'secret draft')
        ->get('searchResults');

    expect($results)->toBeEmpty();
});

test('search excludes articles not visible to the user', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Restricted',
        'content' => '<p>classified payload information</p>',
        'is_published' => true,
        'visibility_mode' => 'whitelist',
    ]);

    $results = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'classified payload')
        ->get('searchResults');

    expect($results)->toBeEmpty();
});

test('search includes package doc results', function (): void {
    app(KnowledgeManager::class)->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $results = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'content here')
        ->get('searchResults');

    expect($results)->toHaveCount(1)
        ->and($results[0]['type'])->toBe('doc')
        ->and($results[0]['path'])->toBe('guides/advanced.md');
});

test('clearing the search empties the results', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Dunning',
        'content' => '<p>Overdue invoices.</p>',
        'is_published' => true,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'overdue');

    expect($component->get('searchResults'))->toHaveCount(1);

    $component->set('search', '');

    expect($component->get('searchResults'))->toBeEmpty();
});
```

Check first whether `KnowledgeArticle::factory()` defaults `visibility_mode` to `public` (`database/factories/KnowledgeArticleFactory.php`); if not, add `'visibility_mode' => 'public'` to each `create()` call so `visibleToUser` passes.

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/testbench package:test --testsuite Livewire --filter KnowledgeSearch`
Expected: FAIL — `searchResults` property does not exist / results empty

- [ ] **Step 3: Implement in `Knowledge.php`**

Add property (alphabetical position near `$search`):

```php
    public array $searchResults = [];
```

Replace `updatedSearch()` (currently reloads trees — tree filtering dies in Task 4):

```php
    public function updatedSearch(): void
    {
        if (mb_strlen($this->search) === 0) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = array_merge(
            $this->searchArticles(),
            app(KnowledgeManager::class)->searchDocs($this->search, Auth::user()),
        );
    }

    protected function searchArticles(): array
    {
        $term = $this->search;

        return resolve_static(KnowledgeArticle::class, 'query')
            ->where('is_published', true)
            ->visibleToUser(Auth::user())
            ->where(function ($query) use ($term): void {
                $query->where('title', 'like', '%'.$term.'%')
                    ->orWhere('content', 'like', '%'.$term.'%')
                    ->orWhereHas('attributeTranslations', function ($query) use ($term): void {
                        $query->whereIn('attribute', ['title', 'content'])
                            ->where('value', 'like', '%'.$term.'%');
                    });
            })
            ->with('categories:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(function (KnowledgeArticle $article) use ($term): array {
                $plainText = strip_tags($article->content ?? '');

                return [
                    'type' => 'article',
                    'id' => $article->getKey(),
                    'title' => $article->title,
                    'breadcrumb' => $article->categories->pluck('name')->implode(' › '),
                    'snippet' => SearchSnippet::make($plainText, $term)
                        ?? SearchSnippet::fallback($plainText),
                ];
            })
            ->toArray();
    }
```

Add import: `use TeamNiftyGmbH\NuxbeKnowledge\Support\SearchSnippet;`

Notes for the implementer:
- The `retrieved` model hook localizes `title`/`content` to the session language automatically, so the snippet is built from the currently selected language. A term matching only another language's translation still lists the article — snippet falls back without `<mark>` (spec'd behavior).
- `categories:id,name` keeps the eager load slim; breadcrumb is the category names joined with `›` (empty string for uncategorized).

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/testbench package:test --testsuite Livewire`
Expected: PASS (all Livewire tests)

- [ ] **Step 5: Commit**

```bash
git add src/Livewire/Knowledge.php tests/Livewire/KnowledgeSearchTest.php
git commit -m "feat: search article and doc content into flat search results"
```

---

### Task 4: Remove obsolete tree filtering

**Files:**
- Modify: `src/Livewire/Knowledge.php`

Trees are hidden while searching (Task 5), so filtering them is dead code.

- [ ] **Step 1: Remove the three title filters in `loadCategories()`**

Delete each occurrence of this line (currently at `Knowledge.php:293`, `:304`, `:321`):

```php
                ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
```

- [ ] **Step 2: Simplify `loadPackageDocs()`**

Replace:

```php
    public function loadPackageDocs(): void
    {
        $user = Auth::user();
        $trees = app(KnowledgeManager::class)->getAllVisibleDocsTrees($user);

        if ($this->search) {
            $trees = array_filter(array_map(function (array $config): array {
                $config['tree'] = $this->filterDocsTree($config['tree']);

                return $config;
            }, $trees), fn (array $config): bool => ! empty($config['tree']));
        }

        $this->packageDocs = $trees;
    }
```

with:

```php
    public function loadPackageDocs(): void
    {
        $this->packageDocs = app(KnowledgeManager::class)->getAllVisibleDocsTrees(Auth::user());
    }
```

- [ ] **Step 3: Delete `filterDocsTree()`** (entire method, currently `Knowledge.php:506-527`)

- [ ] **Step 4: Run all test suites**

Run: `composer test`
Expected: PASS (Feature, Livewire, Unit)

- [ ] **Step 5: Commit**

```bash
git add src/Livewire/Knowledge.php
git commit -m "refactor: drop tree filtering superseded by search results list"
```

---

### Task 5: Sidebar UI — flat result list

**Files:**
- Modify: `resources/views/livewire/knowledge.blade.php`

- [ ] **Step 1: Wrap tree sections and add the result list**

Directly after the search input `<div class="mb-4">…</div>` (line 25-27), open the conditional. The whole block from `{{-- User Categories --}}` through the end of the `{{-- Package Docs --}}` `@endforeach` (lines 29-118) moves into the `@else` branch:

```blade
        @if (mb_strlen($search) > 0)
            {{-- Search Results --}}
            <div class="space-y-1">
                <p class="px-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ count($searchResults) }} {{ __('Results') }}
                </p>

                @foreach ($searchResults as $result)
                    <div
                        wire:key="search-result-{{ $loop->index }}"
                        class="cursor-pointer rounded px-2 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700"
                        @if ($result['type'] === 'article')
                            wire:click="selectArticle({{ $result['id'] }})"
                        @else
                            wire:click="selectPackageDoc('{{ $result['package'] }}', '{{ $result['path'] }}')"
                        @endif
                        x-on:click="sidebarOpen = false"
                    >
                        @if ($result['breadcrumb'])
                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $result['breadcrumb'] }}</p>
                        @endif
                        <p class="text-sm font-medium dark:text-gray-200">{{ $result['title'] }}</p>
                        @if ($result['snippet'])
                            <p class="text-xs text-gray-500 dark:text-gray-400 [&_mark]:rounded [&_mark]:bg-yellow-200 [&_mark]:px-0.5 dark:[&_mark]:bg-yellow-500/40 dark:[&_mark]:text-gray-100">
                                {!! $result['snippet'] !!}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            {{-- existing trees: User Categories, Uncategorized Articles, Package Docs --}}
        @endif
```

`{!! !!}` is safe here: `SearchSnippet` escapes everything and only emits `<mark>`.

- [ ] **Step 2: Remove the auto-open-on-search Alpine watchers**

Trees only render when search is empty, so the watchers from commit `5b43c99` are dead. In all three tree sections replace:

```blade
x-data="{ open: @js(mb_strlen($search) > 0) || false }"
x-init="$watch('$wire.search', value => { if (value.length > 0) open = true })"
```
→ `x-data="{ open: false }"`

```blade
x-data="{ childOpen: @js(mb_strlen($search) > 0) || false }"
x-init="$watch('$wire.search', value => { if (value.length > 0) childOpen = true })"
```
→ `x-data="{ childOpen: false }"`

```blade
x-data="{ open: @js(mb_strlen($search) > 0) }"
x-init="$watch('$wire.search', value => { if (value.length > 0) open = true })"
```
→ `x-data="{ open: false }"`

Also remove `:is-searching="mb_strlen($search) > 0"` from `<x-nuxbe-knowledge::knowledge-item …>` **only if** `resources/views/components/knowledge-item.blade.php` uses the prop solely for auto-opening — check the component first; if it also affects rendering of matches, drop the search-related branches there as well.

- [ ] **Step 3: Smoke check**

Run: `./vendor/bin/testbench package:test --testsuite Livewire`
Expected: PASS (component renders both branches; existing render test covers empty search)

- [ ] **Step 4: Commit**

```bash
git add resources/views
git commit -m "feat: show flat search result list with snippets in sidebar"
```

---

### Task 6: Native Flux lightbox

**Files:**
- Modify: `resources/views/livewire/knowledge.blade.php` (package doc view, lines ~330-394)

- [ ] **Step 1: Replace click handler and delete custom lightbox**

- Change `<div x-data="{ lightboxSrc: null }">` to plain `<div>`.
- In the prose `x-on:click` handler replace:

```js
if ($event.target.tagName === 'IMG') {
    lightboxSrc = $event.target.src;
    return;
}
```

with:

```js
if ($event.target.tagName === 'IMG') {
    $nuxbe.openLightbox($event.target.src);
    return;
}
```

- Delete the entire `{{-- Lightbox --}}` block (the `x-show="lightboxSrc"` overlay incl. backdrop and `<img>`, currently lines 364-394). The global `<x-nuxbe-lightbox>` in the Flux app layout handles display.

- [ ] **Step 2: Smoke check**

Run: `./vendor/bin/testbench package:test --testsuite Livewire`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/knowledge.blade.php
git commit -m "refactor: use global nuxbe lightbox for doc images"
```

---

### Task 7: Pint + full suite

- [ ] **Step 1: Run Pint**

Run: `./vendor/bin/pint`
Expected: no remaining style issues (fixes are fine, review the diff)

- [ ] **Step 2: Full test run**

Run: `composer test`
Expected: PASS across Feature, Livewire, Unit

- [ ] **Step 3: Commit (only if Pint changed files)**

```bash
git add -A
git commit -m "style: run pint"
```
