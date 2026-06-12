<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
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
    $role = Role::create(['name' => 'Restricted Role', 'guard_name' => 'web']);

    $article = KnowledgeArticle::factory()->create([
        'title' => 'Restricted',
        'content' => '<p>classified payload information</p>',
        'is_published' => true,
        'visibility_mode' => 'whitelist',
    ]);
    $article->roles()->attach($role->getKey(), ['permission_level' => 'read']);

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
