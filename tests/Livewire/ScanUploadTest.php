<?php

use FluxErp\Models\Language;
use FluxErp\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use TeamNiftyGmbH\NuxbeKnowledge\Livewire\Knowledge;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;

beforeEach(function (): void {
    $language = Language::factory()->create();
    $this->user = User::factory()->create([
        'language_id' => $language->getKey(),
    ]);
});

test('a scan can be uploaded to an article and lands in the scans collection', function (): void {
    $article = KnowledgeArticle::factory()->create(['is_published' => false]);
    $article->users()->attach($this->user->getKey(), ['permission_level' => 'edit']);

    Livewire::actingAs($this->user)
        ->test(Knowledge::class)
        ->call('selectArticle', $article->getKey())
        ->set('scanUpload', UploadedFile::fake()->image('scan.png', 1200, 1600))
        ->call('uploadScan')
        ->assertHasNoErrors();

    expect($article->fresh()->getMedia('scans'))->toHaveCount(1);
});

test('the scans collection rejects non-document files', function (): void {
    $article = KnowledgeArticle::factory()->create();

    $article->addMedia(UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
        ->toMediaCollection('scans');
})->throws(FileUnacceptableForCollection::class);

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
