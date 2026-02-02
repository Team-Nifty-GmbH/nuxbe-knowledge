<?php

use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\DeleteKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

test('can create knowledge article', function (): void {
    $category = KnowledgeCategory::factory()->create();

    $result = CreateKnowledgeArticle::make([
        'knowledge_category_id' => $category->getKey(),
        'title' => 'Test Article',
        'content' => '<p>Test content</p>',
    ])->validate()->execute();

    expect($result)->toBeInstanceOf(KnowledgeArticle::class)
        ->and($result->title)->toBe('Test Article')
        ->and($result->slug)->toBe('test-article');
});

test('creating article creates initial version', function (): void {
    $category = KnowledgeCategory::factory()->create();

    $article = CreateKnowledgeArticle::make([
        'knowledge_category_id' => $category->getKey(),
        'title' => 'Test Article',
        'content' => '<p>Test content</p>',
    ])->validate()->execute();

    $versions = KnowledgeArticleVersion::where('knowledge_article_id', $article->getKey())->get();

    expect($versions)->toHaveCount(1)
        ->and($versions->first()->version_number)->toBe(1)
        ->and($versions->first()->title)->toBe('Test Article');
});

test('can update knowledge article', function (): void {
    $article = KnowledgeArticle::factory()->create();

    $result = UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'title' => 'Updated Title',
        'content' => '<p>Updated content</p>',
    ])->validate()->execute();

    expect($result->title)->toBe('Updated Title');
});

test('updating article creates new version', function (): void {
    $article = KnowledgeArticle::factory()->create();
    KnowledgeArticleVersion::factory()->create([
        'knowledge_article_id' => $article->getKey(),
        'version_number' => 1,
    ]);

    UpdateKnowledgeArticle::make([
        'id' => $article->getKey(),
        'title' => 'Updated Title',
        'content' => '<p>Updated</p>',
        'change_summary' => 'Updated title',
    ])->validate()->execute();

    $versions = KnowledgeArticleVersion::where('knowledge_article_id', $article->getKey())
        ->orderBy('version_number')
        ->get();

    expect($versions)->toHaveCount(2)
        ->and($versions->last()->version_number)->toBe(2)
        ->and($versions->last()->change_summary)->toBe('Updated title');
});

test('can delete knowledge article', function (): void {
    $article = KnowledgeArticle::factory()->create();

    $result = DeleteKnowledgeArticle::make([
        'id' => $article->getKey(),
    ])->validate()->execute();

    expect($result)->toBeTrue()
        ->and(KnowledgeArticle::find($article->getKey()))->toBeNull()
        ->and(KnowledgeArticle::withTrashed()->find($article->getKey()))->not->toBeNull();
});
