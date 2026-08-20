<?php

use FluxErp\Models\Category;
use FluxErp\Models\Language;
use FluxErp\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $this->language = Language::factory()->create();
    Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
});

function nestedCategory(string $name, ?int $parentId): Category
{
    return Category::query()->create([
        'parent_id' => $parentId,
        'model_type' => morph_alias(KnowledgeArticle::class),
        'name' => $name,
        'is_active' => true,
    ]);
}

test('the category tree keeps every level, not just two', function (): void {
    $user = User::factory()->create(['language_id' => $this->language->getKey()]);

    $level1 = nestedCategory('Konzept', null);
    $level2 = nestedCategory('Gesundheit', $level1->getKey());
    $level3 = nestedCategory('PKV', $level2->getKey());
    $level4 = nestedCategory('Tarife', $level3->getKey());

    $article = KnowledgeArticle::factory()->create([
        'visibility_mode' => 'public',
        'is_published' => true,
        'title' => 'Tief verschachtelt',
    ]);
    $article->categories()->attach($level4->getKey());

    $categories = Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->get('categories');

    expect($categories)->toHaveCount(1)
        ->and($categories[0]['name'])->toBe('Konzept')
        ->and($categories[0]['children'][0]['name'])->toBe('Gesundheit')
        ->and($categories[0]['children'][0]['children'][0]['name'])->toBe('PKV')
        ->and($categories[0]['children'][0]['children'][0]['children'][0]['name'])->toBe('Tarife')
        ->and($categories[0]['children'][0]['children'][0]['children'][0]['articles'][0]['title'])
        ->toBe('Tief verschachtelt');
});

test('an article shows up under every category it belongs to', function (): void {
    $user = User::factory()->create(['language_id' => $this->language->getKey()]);

    $first = nestedCategory('Erste', null);
    $second = nestedCategory('Zweite', null);

    $article = KnowledgeArticle::factory()->create([
        'visibility_mode' => 'public',
        'is_published' => true,
        'title' => 'Doppelt einsortiert',
    ]);
    $article->categories()->attach([$first->getKey(), $second->getKey()]);

    $categories = Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->get('categories');

    expect($categories[0]['articles'][0]['title'])->toBe('Doppelt einsortiert')
        ->and($categories[1]['articles'][0]['title'])->toBe('Doppelt einsortiert');
});

test('an inactive category is left out with its whole branch', function (): void {
    $user = User::factory()->create(['language_id' => $this->language->getKey()]);

    $root = nestedCategory('Sichtbar', null);
    $hidden = nestedCategory('Versteckt', $root->getKey());
    $hidden->update(['is_active' => false]);
    nestedCategory('Unter dem Versteckten', $hidden->getKey());

    $categories = Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->get('categories');

    expect($categories)->toHaveCount(1)
        ->and($categories[0]['children'])->toBeEmpty();
});
