<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('an image dropped into the editor keeps the name it was uploaded under', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);
    $article->users()->attach($this->user->getKey(), ['permission_level' => 'edit']);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->call('editArticle')
        ->set('editorImage', UploadedFile::fake()->image('diagram.png', 800, 600))
        ->call('processEditorImage')
        ->assertHasNoErrors()
        ->assertSet('editorImage', null);

    $media = $article->fresh()->getMedia('editor-images');

    expect($media)->toHaveCount(1)
        ->and($media->first()->name)->toBe('diagram')
        ->and($media->first()->file_name)->toBe('diagram.png');
});

test('a user without edit permission cannot attach an editor image', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->set('editorImage', UploadedFile::fake()->image('diagram.png', 800, 600))
        ->call('processEditorImage')
        ->assertSet('editorImage', null);

    expect($article->fresh()->getMedia('editor-images'))->toHaveCount(0);
});
