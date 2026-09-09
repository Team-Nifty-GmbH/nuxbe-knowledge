<?php

use FluxErp\Actions\Media\UploadMedia;
use FluxErp\Models\Language;
use FluxErp\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('a scan can be uploaded to an article and keeps the name it was uploaded under', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);
    $article->users()->attach($this->user->getKey(), ['permission_level' => 'edit']);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->set('scanUpload', UploadedFile::fake()->image('scan.png', 1200, 1600))
        ->call('uploadScan')
        ->assertHasNoErrors()
        ->assertSet('scanUpload', null);

    $media = $article->fresh()->getMedia('scans');

    expect($media)->toHaveCount(1)
        ->and($media->first()->name)->toBe('scan')
        ->and($media->first()->file_name)->toBe('scan.png');
});

test('a file the scans collection does not take is answered with an error, not an exception', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);
    $article->users()->attach($this->user->getKey(), ['permission_level' => 'edit']);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->set('scanUpload', UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
        ->call('uploadScan')
        ->assertHasErrors('scanUpload');

    expect($article->fresh()->getMedia('scans'))->toHaveCount(0);
});

test('the scans collection reports a rejected file as a validation error the component can catch', function (): void {
    $article = KnowledgeArticle::factory()->create();
    $file = UploadedFile::fake()->create('notes.txt', 1, 'text/plain');

    UploadMedia::make([
        'model_type' => morph_alias(KnowledgeArticle::class),
        'model_id' => $article->getKey(),
        'collection_name' => 'scans',
        'media' => $file->getRealPath(),
        'file_name' => 'notes.txt',
        'name' => 'notes',
    ])
        ->validate()
        ->execute();
})->throws(ValidationException::class);

test('a user without edit permission cannot upload a scan to an article', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->set('scanUpload', UploadedFile::fake()->image('scan.png', 1200, 1600))
        ->call('uploadScan')
        ->assertHasErrors()
        ->assertSet('scanUpload', null);

    expect($article->fresh()->getMedia('scans'))->toHaveCount(0);
});
