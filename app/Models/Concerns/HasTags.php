<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, "taggable");
    }

    public function addTag(Tag $tag): void
    {
        $this->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags()->detach($tag->id);
    }
}
