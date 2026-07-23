<?php

use FluxErp\Models\User;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeSearch;

function article(array $overrides = []): KnowledgeArticle
{
    return KnowledgeArticle::factory()->create(array_merge([
        'title' => 'Drucker einrichten',
        'content' => '<p>Treiber laden und Warteschlange leeren.</p>',
        'content_markdown' => 'Treiber laden und Warteschlange leeren.',
        'is_published' => true,
        'visibility_mode' => 'public',
    ], $overrides));
}

test('a matching published public article is found', function (): void {
    $found = article();

    $ids = collect(app(KnowledgeSearch::class)->search('Drucker', User::factory()->create()))
        ->where('type', 'article')->pluck('id');

    expect($ids)->toContain($found->getKey());
});

test('an unpublished article is never returned', function (): void {
    $hidden = article(['is_published' => false]);

    $ids = collect(app(KnowledgeSearch::class)->search('Drucker', User::factory()->create()))
        ->where('type', 'article')->pluck('id');

    // Scout may rank it, but the publish filter in the hydration query must drop it.
    expect($ids)->not->toContain($hidden->getKey());
});
