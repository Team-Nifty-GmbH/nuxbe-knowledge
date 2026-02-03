<?php

use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\CreateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\DeleteKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeArticle\UpdateKnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;

test('can create knowledge article', function (): void {
    $result = CreateKnowledgeArticle::make([
        'title' => 'Test Article',
        'content' => '<p>Test content</p>',
    ])->validate()->execute();

    expect($result)->toBeInstanceOf(KnowledgeArticle::class)
        ->and($result->title)->toBe('Test Article')
        ->and($result->slug)->toBe('test-article');
});

test('creating article creates initial version', function (): void {
    $article = CreateKnowledgeArticle::make([
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
        ->and(KnowledgeArticle::query()->whereKey($article->getKey())->first())->toBeNull()
        ->and(KnowledgeArticle::withTrashed()->whereKey($article->getKey())->first())->not->toBeNull();
});
