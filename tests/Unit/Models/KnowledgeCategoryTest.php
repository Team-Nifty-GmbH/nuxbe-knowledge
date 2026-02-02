<?php

use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeArticle;
use TeamNiftyGmbH\NuxbeKnowledge\Models\KnowledgeCategory;

test('factory creates valid category', function (): void {
    $category = KnowledgeCategory::factory()->create();

    expect($category)->toBeInstanceOf(KnowledgeCategory::class)
        ->and($category->exists)->toBeTrue()
        ->and($category->uuid)->toBeString();
});

test('category has articles relationship', function (): void {
    $category = KnowledgeCategory::factory()->create();
    KnowledgeArticle::factory()->create(['knowledge_category_id' => $category->getKey()]);

    expect($category->articles)->toHaveCount(1)
        ->and($category->articles->first())->toBeInstanceOf(KnowledgeArticle::class);
});

test('category has children relationship', function (): void {
    $parent = KnowledgeCategory::factory()->create();
    $child = KnowledgeCategory::factory()->create(['parent_id' => $parent->getKey()]);

    expect($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->getKey())->toBe($child->getKey());
});

test('category has parent relationship', function (): void {
    $parent = KnowledgeCategory::factory()->create();
    $child = KnowledgeCategory::factory()->create(['parent_id' => $parent->getKey()]);

    expect($child->parent)->toBeInstanceOf(KnowledgeCategory::class)
        ->and($child->parent->getKey())->toBe($parent->getKey());
});
