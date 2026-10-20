<?php

use FluxErp\Livewire\Settings\EmailTemplates;
use FluxErp\Models\Language;
use FluxErp\Models\User;
use FluxErp\View\Components\Editor;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\View\Components\EditorButtons\KnowledgeImageUpload;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('the email template editor does not offer the knowledge image upload', function (): void {
    Livewire::actingAs($this->user)
        ->test(EmailTemplates::class)
        ->assertStatus(200)
        ->assertDontSee('editorImage', false);
});

test('an editor without a scope does not offer the knowledge image upload', function (): void {
    expect((new Editor)->getButtonInstance(KnowledgeImageUpload::class))->toBeNull()
        ->and((new Editor(scope: 'knowledge'))->getButtonInstance(KnowledgeImageUpload::class))->not->toBeNull();
});

test('the knowledge editor offers the image upload', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);
    $article->users()->attach($this->user->getKey(), ['permission_level' => 'edit']);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->call('editArticle')
        ->assertSee('editorImage', false);
});
