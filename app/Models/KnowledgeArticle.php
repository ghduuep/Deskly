<?php

namespace App\Models;

use App\Casts\SlugCast;
use App\Events\KnowledgeArticle\KnowledgeArticleArchived;
use App\Events\KnowledgeArticle\KnowledgeArticlePublished;
use App\Events\KnowledgeArticle\KnowledgeArticleRestored;
use App\Events\KnowledgeArticle\KnowledgeArticleUnpublished;
use App\KnowledgeArticleStatus;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    "knowledge_category_id",
    "author_id",
    "title",
    "slug",
    "content",
    "status",
    "published_at",
])]
class KnowledgeArticle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            "status" => KnowledgeArticleStatus::class,
            "published_at" => "datetime",
            "slug" => SlugCast::class,
        ];
    }

    /**
     * Publishes a draft article and records the publication timestamp.
     *
     * Does nothing if the article is already published.
     *
     * @throws DomainException If the article is not a draft.
     */
    public function publish(): void
    {
        $article = DB::transaction(function () {
            $article = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($article->status === KnowledgeArticleStatus::Published) {
                return;
            }

            if ($article->status !== KnowledgeArticleStatus::Draft) {
                throw new DomainException(
                    "Only draft articles can be published.",
                );
            }
            $article->update([
                "status" => KnowledgeArticleStatus::Published,
                "published_at" => now(),
            ]);

            KnowledgeArticlePublished::dispatch($article->id);

            return $article;
        });

        $this->setRawAttributes($article->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Unpublish a published article.
     *
     * Does nothing if the article is already a draft.
     *
     * @throws DomainException If the article is not published.
     */
    public function unpublish(): void
    {
        $article = DB::transaction(function () {
            $article = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($article->status === KnowledgeArticleStatus::Draft) {
                return;
            }

            if ($article->status !== KnowledgeArticleStatus::Published) {
                throw new DomainException(
                    "Only published articles can be unpublished.",
                );
            }
            $article->update([
                "status" => KnowledgeArticleStatus::Draft,
                "published_at" => null,
            ]);

            KnowledgeArticlePublished::dispatch($article->id);

            return $article;
        });

        $this->setRawAttributes($article->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Archives the article regardless of its current status.
     *
     * Does nothing if the article is already archived.
     */
    public function archive(): void
    {
        $article = DB::transaction(function () {
            $article = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($article->status === KnowledgeArticleStatus::Archived) {
                return;
            }
            $article->update(["status" => KnowledgeArticleStatus::Archived]);

            KnowledgeArticleArchived::dispatch($article->id);

            return $article;
        });

        $this->setRawAttributes($article->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Restores an archived article to draft status.
     *
     * @throws DomainException If the article is not archived.
     */
    public function restore(): void
    {
        $article = DB::transaction(function () {
            $article = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($article->status !== KnowledgeArticleStatus::Archived) {
                throw new DomainException(
                    "Only archived articles can be restored.",
                );
            }
            $article->update(["status" => KnowledgeArticleStatus::Draft]);

            KnowledgeArticleRestored::dispatch($article->id);

            return $article;
        });

        $this->setRawAttributes($article->getAttributes(), true);
        $this->unsetRelations();
    }

    public function isPublished(): bool
    {
        return $this->status === KnowledgeArticleStatus::Published;
    }

    /**
     * Assigns a category to the article.
     *
     * Does nothing if the category is already assigned.
     */
    public function assignToCategory(KnowledgeCategory $category): void
    {
        if ($this->knowledge_category_id === $category->id) {
            return;
        }

        $this->update([
            "knowledge_category_id" => $category->id,
        ]);
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
