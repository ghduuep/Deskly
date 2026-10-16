<?php

namespace App\Models;

use App\ArticleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    protected function casts(): array
    {
        return [
            "status" => ArticleStatus::class,
            "published_at" => "datetime",
        ];
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
