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

test('sidebar search filters uncategorized articles by title', function (): void {
    KnowledgeArticle::factory()->create(['title' => 'Dunning', 'is_published' => true]);
    KnowledgeArticle::factory()->create(['title' => 'Payment Runs', 'is_published' => true]);

    $component = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'dunning');

    $titles = array_column($component->get('uncategorizedArticles'), 'title');

    expect($titles)->toBe(['Dunning']);
});

test('sidebar search filters package doc trees by file name', function (): void {
    app(KnowledgeManager::class)->registerDocs(
        package: 'test-package',
        path: __DIR__.'/../fixtures/docs',
        label: 'Test Docs',
    );

    $component = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'advanced');

    $tree = $component->get('packageDocs')['test-package']['tree'] ?? [];
    $names = collect($tree)->flatMap(function (array $item): array {
        return ($item['type'] ?? '') === 'directory'
            ? array_column($item['children'] ?? [], 'name')
            : [$item['name']];
    })->all();

    expect($names)->toBe(['Advanced']);
});

test('clearing the sidebar search restores all entries', function (): void {
    KnowledgeArticle::factory()->create(['title' => 'Dunning', 'is_published' => true]);
    KnowledgeArticle::factory()->create(['title' => 'Payment Runs', 'is_published' => true]);

    $component = Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->set('search', 'dunning')
        ->set('search', '');

    expect($component->get('uncategorizedArticles'))->toHaveCount(2);
});
