<?php

use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

test('can register package docs', function (): void {
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
