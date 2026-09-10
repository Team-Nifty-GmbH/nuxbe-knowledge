<?php

use FluxErp\Settings\SearchSettings;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    config(['scout.driver' => 'meilisearch']);

    SearchSettings::fake([
        'semantic_search_enabled' => true,
        'embedder_url' => 'https://ai.example.test/v1/embeddings',
        'embedder_api_key' => 'test-key',
        'embedder_model' => 'embed',
        'embedder_dimensions' => 4096,
        'semantic_ratio' => 0.5,
    ]);
});

test('the article embedder embeds title and markdown through a document template', function (): void {
    $definition = KnowledgeArticle::scoutEmbedders()['default'];

    expect($definition['documentTemplate'])->toContain('doc.title')
        ->and($definition['documentTemplate'])->toContain('doc.content_markdown')
        ->and($definition['documentTemplateMaxBytes'])->toBe(20000)
        ->and($definition['source'])->toBe('rest')
        ->and($definition['dimensions'])->toBe(4096);
});

test('the embedder stays off without active search settings', function (): void {
    SearchSettings::fake(['semantic_search_enabled' => false, 'embedder_url' => '',
        'embedder_api_key' => null, 'embedder_model' => 'embed', 'embedder_dimensions' => 4096,
        'semantic_ratio' => 0.5]);

    expect(KnowledgeArticle::scoutEmbedders())->toBeNull();
});
