<?php

use App\Models\KnowledgeCategory;

it("identifies a root category", function () {
    $category = KnowledgeCategory::factory()->create();

    expect($category->isRoot())->toBeTrue();
});

it("identifies a category with children", function () {
    $parent = KnowledgeCategory::factory()->create();

    KnowledgeCategory::factory()->create([
        "parent_id" => $parent->id,
    ]);

    expect($parent->hasChildren())->toBeTrue();
});

it("prevents moving a category under itself", function () {
    $category = KnowledgeCategory::factory()->create();

    expect(fn () => $category->moveTo($category))->toThrow(DomainException::class);
});

it("prevents deleting a category with children", function () {
    $parent = KnowledgeCategory::factory()->create();

    KnowledgeCategory::factory()->create([
        "parent_id" => $parent->id,
    ]);

    expect(fn () => $parent->remove())->toThrow(DomainException::class);
    expect(KnowledgeCategory::find($parent->id))->not->toBeNull();
});
