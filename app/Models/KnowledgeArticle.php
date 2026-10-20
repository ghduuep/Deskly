<?php

namespace App\Models;

use App\Casts\SlugCast;
use App\KnowledgeArticleStatus;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'knowledge_category_id',
        'author_id',
        'title',
        'slug',
        'content',
        'status',
        'published_at',
    ]),
]
class KnowledgeArticle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => KnowledgeArticleStatus::class,
            'published_at' => 'datetime',
            'slug' => SlugCast::class,
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
        if ($this->status === KnowledgeArticleStatus::Published) {
            return;
        }

        if ($this->status !== KnowledgeArticleStatus::Draft) {
            throw new DomainException('Only draft articles can be published.');
        }
        $this->update([
            'status' => KnowledgeArticleStatus::Published,
            'published_at' => now(),
        ]);
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
        if ($this->status === KnowledgeArticleStatus::Draft) {
            return;
        }

        if ($this->status !== KnowledgeArticleStatus::Published) {
            throw new DomainException(
                'Only published articles can be unpublished.',
            );
        }
        $this->update([
            'status' => KnowledgeArticleStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Archives the article regardless of its current status.
     *
     * Does nothing if the article is already archived.
     */
    public function archive(): void
    {
        if ($this->status === KnowledgeArticleStatus::Archived) {
            return;
        }
        $this->update(['status' => KnowledgeArticleStatus::Archived]);
    }

    /**
     * Restores an archived article to draft status.
     *
     * @throws DomainException If the article is not archived.
     */
    public function restore(): void
    {
        if ($this->status !== KnowledgeArticleStatus::Archived) {
            throw new DomainException(
                'Only archived articles can be restored.',
            );
        }
        $this->update(['status' => KnowledgeArticleStatus::Draft]);
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

        $this->category()->associate($category);
        $this->save();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeCategory::class,
            'knowledge_category_id',
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
