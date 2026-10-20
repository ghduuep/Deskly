<?php

namespace App\Models;

use App\Casts\SlugCast;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "slug", "description", "parent_id"])]
class KnowledgeCategory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            "slug" => SlugCast::class,
        ];
    }
    /**
     * Moves the category beneath another category.
     *
     * Prevents self-parenting and circular hierarchies.
     *
     * @throws DomainException If the move would create a cycle.
     */
    public function moveTo(KnowledgeCategory $parent): void
    {
        if ($this->is($parent)) {
            throw new DomainException("A category cannot be its own parent.");
        }

        if ($parent->isDescendantOf($this)) {
            throw new DomainException(
                "A category cannot be moved under its descendant.",
            );
        }

        $this->parent()->associate($parent);
        $this->save();
    }

    /**
     * Determines whether this category is a descendant of another category.
     *
     * Traverses the parent hierarchy to check both direct and indirect ancestors.
     */
    public function isDescendantOf(KnowledgeCategory $category): bool
    {
        $parent = $this->parent;

        while ($parent !== null) {
            if ($parent->is($category)) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }

    public function makeRoot(): void
    {
        $this->parent()->dissociate();
        $this->save();
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function canBeDeleted(): bool
    {
        return !$this->articles()->exists() && !$this->children()->exists();
    }

    /**
     * Deletes the category if it has no articles or subcategories.
     *
     * @throws DomainException If the category is not empty.
     */
    public function remove(): void
    {
        if (!$this->canBeDeleted()) {
            throw new DomainException(
                "Cannot delete a category containing articles or subcategories.",
            );
        }

        $this->delete();
    }

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
