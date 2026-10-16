<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "slug", "description", "parent_id"])]
class KnowledgeCategory extends Model
{
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class, "parent_id");
    }
}
