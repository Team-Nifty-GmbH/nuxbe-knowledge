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

test('returns null for non-registered package', function (): void {
    $manager = new KnowledgeManager;

    expect($manager->resolveLanguagePath('non-existent'))->toBeNull();
});
