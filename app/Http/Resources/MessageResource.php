<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_internal' => $this->isInternal(),
            'direction' => $this->direction,
            'author' => new ContactResource($this->whenLoaded('author')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
