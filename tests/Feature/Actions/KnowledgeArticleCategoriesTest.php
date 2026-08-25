<?php

use FluxErp\Models\Category;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

function knowledgeCategory(string $name): Category
{
    return Category::query()->create([
        'name' => $name,
        'model_type' => 'knowledge_article',
    ]);
}

test('create syncs the categories it was given', function (): void {
    $category = knowledgeCategory('Ella Biotech');

    $article = CreateKnowledgeArticle::make([
        'title' => 'Oligo-Sequenzen prüfen',
        'content' => '<p>Text</p>',
        'categories' => [$category->getKey()],
    ])->validate()->execute();

    expect($article->categories()->pluck('categories.id')->all())->toBe([$category->getKey()]);
});

test('update replaces the categories of an article', function (): void {
    $first = knowledgeCategory('SBM Verlag');
    $second = knowledgeCategory('Capital C Estate');

    $article = KnowledgeArticle::factory()->create();
    $article->categories()->sync([$first->getKey()]);

    UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'categories' => [$second->getKey()],
    ])->validate()->execute();

    expect($article->categories()->pluck('categories.id')->all())->toBe([$second->getKey()]);
});

test('omitting categories leaves the existing ones alone', function (): void {
    $category = knowledgeCategory('WestfalenBahn');

    $article = KnowledgeArticle::factory()->create();
    $article->categories()->sync([$category->getKey()]);

    UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'title' => 'Neuer Titel',
    ])->validate()->execute();

    expect($article->categories()->pluck('categories.id')->all())->toBe([$category->getKey()])
        ->and($article->fresh()->title)->toBe('Neuer Titel');
});
