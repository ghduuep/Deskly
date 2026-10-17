<?php

use App\KnowledgeArticleStatus;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;

it("it should create an article default draft status", function () {
    $article = KnowledgeArticle::factory()->create();

    expect($article->fresh()->status)->toBe(KnowledgeArticleStatus::Draft);
});

it("can assign a category to an article", function() {
    $article = KnowledgeArticle::factory()->create([
        "knowledge_category_id" => null,
    ]);
    $category = KnowledgeCategory::factory()->create();

    $article->assignToCategory($category);

    expect($article->fresh()->category->id)->toBe($category->id);
});

it("can publish an article", function () {
    $article = KnowledgeArticle::factory()->create();

    $article->publish();

    expect($article->fresh()->status)->toBe(KnowledgeArticleStatus::Published)->and($article->fresh()->published_at)->not->toBeNull();
});

it("can unpublish an article", function() {
    $article = KnowledgeArticle::factory()->create([
        "status" => KnowledgeArticleStatus::Published,
    ]);

    $article->unpublish();

    expect($article->fresh()->status)->toBe(KnowledgeArticleStatus::Draft);
});

it("can archive an article", function() {
    $article = KnowledgeArticle::factory()->create();

    $article->archive();

    expect($article->fresh()->status)->toBe(KnowledgeArticleStatus::Archived);
});

it("can restore an archived article", function() {
    $article = KnowledgeArticle::factory()->create([
        "status" => KnowledgeArticleStatus::Archived,
    ]);

    $article->restore();

    expect($article->fresh()->status)->toBe(KnowledgeArticleStatus::Draft);
});

it("should not unpublished not published articles", function(KnowledgeArticleStatus $status) {
    $article = KnowledgeArticle::factory()->create([
        "status" => $status,
    ]);

    expect(fn () => $article->unpublish())->toThrow(DomainException::class);
})->with([
    "draft articles cannot be unpublished" => [KnowledgeArticleStatus::Draft],
    "archived articles cannot be unpublished" => [KnowledgeArticleStatus::Archived],
]);

it("should not restore not archived articles", function(KnowledgeArticleStatus $status) {
    $article = KnowledgeArticle::factory()->create([
        "status" => $status,
    ]);

    expect(fn () => $article->restore())->toThrow(DomainException::class);
})->with([
    "draft articles cannot be restored" => [KnowledgeArticleStatus::Draft],
    "published articles cannot be restored" => [KnowledgeArticleStatus::Published],
]);
