<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Forum\Models\Board;

/**
 * @mixin Board
 */
final class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'state' => $this->state->value,
            'visibility' => $this->visibility->value,
            'thread_count' => $this->thread_count,
            'post_count' => $this->post_count,
            'last_post_at' => $this->last_post_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'children' => BoardResource::collection($this->whenLoaded('children')),
        ];
    }
}
