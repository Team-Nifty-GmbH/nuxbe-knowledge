<?php

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticleVersion;

test('factory creates valid article', function (): void {
    $article = KnowledgeArticle::factory()->create();

    expect($article)->toBeInstanceOf(KnowledgeArticle::class)
        ->and($article->exists)->toBeTrue()
        ->and($article->uuid)->toBeString();
});

test('article has categories relationship', function (): void {
    $article = KnowledgeArticle::factory()->create();

    expect($article->categories())->toBeInstanceOf(MorphToMany::class);
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
        ->and(KnowledgeArticle::withTrashed()->whereKey($article->getKey())->first())->not->toBeNull();
});
