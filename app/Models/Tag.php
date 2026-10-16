<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(["name", "color"])]
class Tag extends Model
{
    use HasFactory;

    public function tickets(): MorphToMany
    {
        return $this->morphedByMany(Ticket::class, "taggable");
    }

    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, "taggable");
    }
}
