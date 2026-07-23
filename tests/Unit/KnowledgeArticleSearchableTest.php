<?php

use FluxErp\Traits\Scout\Searchable;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

test('a knowledge article is scout searchable', function (): void {
    expect(in_array(Searchable::class, class_uses_recursive(KnowledgeArticle::class), true))
        ->toBeTrue();
});

test('the searchable array carries the title and the markdown body', function (): void {
    $article = KnowledgeArticle::factory()->make([
        'title' => 'VPN einrichten',
        'content_markdown' => 'Schritt fuer Schritt: Client installieren und Profil laden.',
        'is_published' => true,
        'visibility_mode' => 'public',
    ]);

    $searchable = $article->toSearchableArray();

    // content_markdown is the clean text copy the semantic index needs; content (HTML) is not indexed.
    expect($searchable)->toMatchArray([
        'title' => 'VPN einrichten',
        'content_markdown' => 'Schritt fuer Schritt: Client installieren und Profil laden.',
        'is_published' => true,
        'visibility_mode' => 'public',
    ])->and($searchable)->toHaveKey('id');
});
