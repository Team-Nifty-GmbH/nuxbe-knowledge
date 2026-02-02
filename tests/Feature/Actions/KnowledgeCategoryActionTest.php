<?php

use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory\CreateKnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory\DeleteKnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Actions\KnowledgeCategory\UpdateKnowledgeCategory;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

test('can create knowledge category', function (): void {
    $result = CreateKnowledgeCategory::make([
        'name' => 'Test Category',
    ])->validate()->execute();

    expect($result)->toBeInstanceOf(KnowledgeCategory::class)
        ->and($result->name)->toBe('Test Category')
        ->and($result->slug)->toBe('test-category');
});

test('can create child category', function (): void {
    $parent = KnowledgeCategory::factory()->create();

    $result = CreateKnowledgeCategory::make([
        'name' => 'Child Category',
        'parent_id' => $parent->getKey(),
    ])->validate()->execute();

    expect($result->parent_id)->toBe($parent->getKey());
});

test('can update knowledge category', function (): void {
    $category = KnowledgeCategory::factory()->create();

    $result = UpdateKnowledgeCategory::make([
        'id' => $category->getKey(),
        'name' => 'Updated Name',
    ])->validate()->execute();

    expect($result->name)->toBe('Updated Name');
});

test('can delete knowledge category', function (): void {
    $category = KnowledgeCategory::factory()->create();

    $result = DeleteKnowledgeCategory::make([
        'id' => $category->getKey(),
    ])->validate()->execute();

    expect($result)->toBeTrue()
        ->and(KnowledgeCategory::find($category->getKey()))->toBeNull();
});
