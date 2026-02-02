<?php

use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

test('factory creates valid article', function (): void {
    $article = KnowledgeArticle::factory()->create();

    expect($article)->toBeInstanceOf(KnowledgeArticle::class)
        ->and($article->exists)->toBeTrue()
        ->and($article->uuid)->toBeString();
});

test('article belongs to category', function (): void {
    $article = KnowledgeArticle::factory()->create();

    expect($article->category)->toBeInstanceOf(KnowledgeCategory::class);
});

test('article has versions relationship', function (): void {
    $article = KnowledgeArticle::factory()->create();
    KnowledgeArticleVersion::factory()->create(['knowledge_article_id' => $article->getKey()]);

    expect($article->versions)->toHaveCount(1)
        ->and($article->versions->first())->toBeInstanceOf(KnowledgeArticleVersion::class);
});

test('article uses soft deletes', function (): void {
    $article = KnowledgeArticle::factory()->create();
    $article->delete();

    expect($article->trashed())->toBeTrue()
        ->and(KnowledgeArticle::withTrashed()->find($article->getKey()))->not->toBeNull();
});
