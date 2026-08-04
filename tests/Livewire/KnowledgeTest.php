<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

test('knowledge component can render', function (): void {
    $language = Language::factory()->create();
    $user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);

    Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->assertStatus(200);
});

test('article view renders heading anchors', function (): void {
    $language = Language::factory()->create();
    $user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);

    $article = KnowledgeArticle::factory()->create([
        'content' => '<h2>Dunning Levels</h2><p>body</p>',
        'is_published' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->assertSeeHtml('id="dunning-levels"')
        ->assertSeeHtml('<a href="#dunning-levels" class="heading-anchor" data-heading-anchor>#</a>');
});

test('an editor can reveal unpublished drafts in the tree', function (): void {
    $language = Language::factory()->create();
    $user = User::factory()->create(['language_id' => $language->getKey()]);

    KnowledgeArticle::factory()->create([
        'title' => 'Geheimer Entwurf',
        'is_published' => false,
        'visibility_mode' => 'public',
    ]);

    Livewire::actingAs($user)
        ->test(Knowledge::class)
        // A draft stays hidden in the normal tree...
        ->assertDontSee('Geheimer Entwurf')
        // ...until an editor asks to see drafts.
        ->set('showDrafts', true)
        ->assertSee('Geheimer Entwurf');
});
