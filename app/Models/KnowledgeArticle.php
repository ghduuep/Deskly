<?php

namespace App\Models;

use App\ArticleStatus;
use App\KnowledgeArticleStatus;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        "knowledge_category_id",
        "author_id",
        "title",
        "slug",
        "content",
        "status",
        "published_at",
    ]),
]
class KnowledgeArticle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            "status" => KnowledgeArticleStatus::class,
            "published_at" => "datetime",
        ];
    }

    public function publish(): void
    {
        if ($this->status !== KnowledgeArticleStatus::Draft) {
            throw new DomainException("Only draft articles can be published.");
        }
        $this->update(["status" => KnowledgeArticleStatus::Published, "published_at" => now(),]);
    }

    public function unpublish(): void
    {
        if ($this->status !== KnowledgeArticleStatus::Published) {
            throw new DomainException("Only published articles can be unpublished.");
        }
        $this->update(["status" => KnowledgeArticleStatus::Draft, "published_at" => null]);
    }

    public function archive(): void
    {
        $this->update(["status" => KnowledgeArticleStatus::Archived]);
    }

    public function restore(): void
    {
        if ($this->status !== KnowledgeArticleStatus::Archived) {
            throw new DomainException("Only archived articles can be restored.");
        }
        $this->update(["status" => KnowledgeArticleStatus::Draft]);
    }

    public function isPublished(): bool
    {
        return $this->status === KnowledgeArticleStatus::Published;
    }

    public function assignToCategory(KnowledgeCategory $category): void
    {
        $this->category()->associate($category);
        $this->save();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeCategory::class,
            "knowledge_category_id",
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, "author_id");
    }
}
