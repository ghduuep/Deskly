<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "status" => $this->status,
            "priority" => $this->priority,
            "requester" => new ContactResource($this->whenLoaded("requester")),
            "assignee" => new UserResource($this->whenLoaded("assignee")),
            "company" => $this->whenLoaded("company"),
            "tags" => $this->whenLoaded("tags"),
            "created_at" => $this->created_at?->toISOString(),
            "updated_at" => $this->updated_at?->toISOString(),
        ];
    }
}
