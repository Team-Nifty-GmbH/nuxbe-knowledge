<?php

use Illuminate\Support\Facades\Schema;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

test('it copies the condensed source onto the article', function (): void {
    Schema::create('flux_ai_condensed_sources', function ($table): void {
        $table->id();
        $table->string('source_type');
        $table->unsignedBigInteger('source_id');
        $table->unsignedBigInteger('knowledge_article_id')->nullable();
    });

    $article = KnowledgeArticle::factory()->create();
    $untouched = KnowledgeArticle::factory()->create(['source_type' => 'order', 'source_id' => 1]);

    DB::table('flux_ai_condensed_sources')->insert([
        ['source_type' => 'ticket', 'source_id' => 4711, 'knowledge_article_id' => $article->getKey()],
        ['source_type' => 'ticket', 'source_id' => 4712, 'knowledge_article_id' => $untouched->getKey()],
        ['source_type' => 'ticket', 'source_id' => 4713, 'knowledge_article_id' => null],
    ]);

    $this->artisan('knowledge:backfill-article-sources')->assertSuccessful();

    expect($article->fresh()->source_type)->toBe('ticket')
        ->and($article->fresh()->source_id)->toBe(4711)
        ->and($untouched->fresh()->source_id)->toBe(1);

    Schema::drop('flux_ai_condensed_sources');
});

test('it does nothing without the flux-ai table', function (): void {
    $this->artisan('knowledge:backfill-article-sources')->assertSuccessful();
});
