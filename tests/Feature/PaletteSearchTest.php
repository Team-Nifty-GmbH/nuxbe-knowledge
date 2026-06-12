<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('palette search returns article options with plain text description', function (): void {
    $article = KnowledgeArticle::factory()->create([
        'title' => 'Dunning',
        'content' => '<h2>Levels</h2><p>Overdue invoices are processed automatically.</p>',
        'is_published' => true,
    ]);

    $response = $this->actingAs($this->user, 'web')
        ->getJson(route('knowledge.palette-search', ['search' => 'overdue invoices']));

    $response->assertOk();
    $options = $response->json();

    expect($options)->toHaveCount(1)
        ->and($options[0]['label'])->toBe('Dunning')
        ->and($options[0]['value'])->toBe('article:'.$article->getKey())
        ->and($options[0]['type'])->toBe('article')
        ->and($options[0]['id'])->toBe($article->getKey())
        ->and($options[0]['description'])->toContain('Overdue invoices')
        ->and($options[0]['description'])->not->toContain('<mark>');
});

test('palette search returns package doc options', function (): void {
    app(KnowledgeManager::class)->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $response = $this->actingAs($this->user, 'web')
        ->getJson(route('knowledge.palette-search', ['search' => 'content here']));

    $response->assertOk();
    $options = $response->json();

    expect($options)->toHaveCount(1)
        ->and($options[0]['type'])->toBe('doc')
        ->and($options[0]['package'])->toBe('test-package')
        ->and($options[0]['path'])->toBe('guides/advanced.md')
        ->and($options[0]['value'])->toBe('doc:test-package:guides/advanced.md')
        ->and($options[0]['description'])->toContain('Test Docs › Guides');
});

test('palette search excludes unpublished articles', function (): void {
    KnowledgeArticle::factory()->create([
        'title' => 'Draft',
        'content' => '<p>secret draft content</p>',
        'is_published' => false,
    ]);

    $this->actingAs($this->user, 'web')
        ->getJson(route('knowledge.palette-search', ['search' => 'secret draft']))
        ->assertOk()
        ->assertExactJson([]);
});

test('palette search returns empty for empty term', function (): void {
    KnowledgeArticle::factory()->create(['title' => 'Dunning', 'is_published' => true]);

    $this->actingAs($this->user, 'web')
        ->getJson(route('knowledge.palette-search'))
        ->assertOk()
        ->assertExactJson([]);
});

test('palette search requires authentication', function (): void {
    $this->getJson(route('knowledge.palette-search', ['search' => 'foo']))
        ->assertUnauthorized();
});
