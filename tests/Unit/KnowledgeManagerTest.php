<?php

use FluxErp\Models\Language;
use Illuminate\Support\Facades\Session;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

test('can register package docs with string path', function (): void {
    $manager = new KnowledgeManager;

    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
        icon: 'book-open',
    );

    $registered = $manager->getRegisteredPackages();

    expect($registered)->toHaveKey('test-package')
        ->and($registered['test-package']['label'])->toBe('Test Docs')
        ->and($registered['test-package']['icon'])->toBe('book-open');
});

test('can register package docs with array paths', function (): void {
    $manager = new KnowledgeManager;

    $manager->registerDocs(
        package: 'test-package',
        path: [
            'de' => __DIR__.'/../fixtures/docs',
            'en' => __DIR__.'/../fixtures/docs-en',
        ],
        label: 'Test Docs',
        icon: 'book-open',
    );

    $registered = $manager->getRegisteredPackages();

    expect($registered)->toHaveKey('test-package')
        ->and($registered['test-package']['label'])->toBe('Test Docs')
        ->and($registered['test-package']['paths'])->toHaveKeys(['de', 'en']);
});

test('can get docs tree from filesystem', function (): void {
    $manager = new KnowledgeManager;

    $fixturePath = __DIR__.'/../fixtures/docs';

    $manager->registerDocs(
        package: 'test-package',
        path: $fixturePath,
        label: 'Test Docs',
    );

    $tree = $manager->getDocsTree('test-package');

    expect($tree)->toBeArray()
        ->and($tree)->not->toBeEmpty();
});

test('can render markdown to html', function (): void {
    $manager = new KnowledgeManager;

    $fixturePath = __DIR__.'/../fixtures/docs';

    $manager->registerDocs(
        package: 'test-package',
        path: $fixturePath,
        label: 'Test Docs',
    );

    $tree = $manager->getDocsTree('test-package');
    $firstDoc = collect($tree)->first(fn ($item) => ($item['type'] ?? null) === 'file');

    if ($firstDoc) {
        $html = $manager->renderDoc('test-package', $firstDoc['relative_path']);
        expect($html)->toBeString()->toContain('<');
    }
});

test('resolves default path for string registered docs', function (): void {
    $manager = new KnowledgeManager;

    $fixturePath = __DIR__.'/../fixtures/docs';

    $manager->registerDocs(
        package: 'test-package',
        path: $fixturePath,
        label: 'Test Docs',
    );

    expect($manager->resolveLanguagePath('test-package'))->toBe($fixturePath);
});

test('resolves language path based on session language', function (): void {
    $language = Language::factory()->create(['language_code' => 'en']);

    Session::put('selectedLanguageId', $language->getKey());

    $manager = new KnowledgeManager;

    $dePath = __DIR__.'/../fixtures/docs';
    $enPath = __DIR__.'/../fixtures/docs-en';

    $manager->registerDocs(
        package: 'test-package',
        path: ['de' => $dePath, 'en' => $enPath],
        label: 'Test Docs',
    );

    expect($manager->resolveLanguagePath('test-package'))->toBe($enPath);
});

test('falls back to default language when session language has no docs', function (): void {
    Language::factory()->create(['language_code' => 'de', 'is_default' => true]);
    $frLanguage = Language::factory()->create(['language_code' => 'fr', 'is_default' => false]);

    Session::put('selectedLanguageId', $frLanguage->getKey());

    $manager = new KnowledgeManager;

    $dePath = __DIR__.'/../fixtures/docs';
    $enPath = __DIR__.'/../fixtures/docs-en';

    $manager->registerDocs(
        package: 'test-package',
        path: ['de' => $dePath, 'en' => $enPath],
        label: 'Test Docs',
    );

    expect($manager->resolveLanguagePath('test-package'))->toBe($dePath);
});

test('falls back to first available path when no matching language', function (): void {
    Language::factory()->create(['language_code' => 'fr', 'is_default' => true]);
    $esLanguage = Language::factory()->create(['language_code' => 'es', 'is_default' => false]);

    Session::put('selectedLanguageId', $esLanguage->getKey());

    $manager = new KnowledgeManager;

    $dePath = __DIR__.'/../fixtures/docs';
    $enPath = __DIR__.'/../fixtures/docs-en';

    $manager->registerDocs(
        package: 'test-package',
        path: ['de' => $dePath, 'en' => $enPath],
        label: 'Test Docs',
    );

    expect($manager->resolveLanguagePath('test-package'))->toBe($dePath);
});

test('gets docs tree for correct language', function (): void {
    $language = Language::factory()->create(['language_code' => 'en']);

    Session::put('selectedLanguageId', $language->getKey());

    $manager = new KnowledgeManager;

    $manager->registerDocs(
        package: 'test-package',
        path: [
            'de' => __DIR__.'/../fixtures/docs',
            'en' => __DIR__.'/../fixtures/docs-en',
        ],
        label: 'Test Docs',
    );

    $tree = $manager->getDocsTree('test-package');

    expect($tree)->toBeArray()->not->toBeEmpty();

    $firstDoc = collect($tree)->first(fn ($item) => ($item['type'] ?? null) === 'file');
    $html = $manager->renderDoc('test-package', $firstDoc['relative_path']);

    expect($html)->toContain('English test doc');
});

test('renders blade directives in markdown docs', function (): void {
    app()->setLocale('de');

    $manager = new KnowledgeManager;

    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $html = $manager->renderDoc('test-package', 'blade-test.md');

    expect($html)
        ->toContain('Current locale: de')
        ->not->toContain('{{');
});

test('returns null for non-registered package', function (): void {
    $manager = new KnowledgeManager;

    expect($manager->resolveLanguagePath('non-existent'))->toBeNull();
});

test('renderDoc adds heading anchors', function (): void {
    $manager = new KnowledgeManager;
    $manager->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $html = $manager->renderDoc('test-package', 'getting-started.md');

    expect($html)->toContain('id="getting-started"')
        ->toContain('<a href="#getting-started" class="heading-anchor" data-heading-anchor>#</a>');
});

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
