<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Forum\Models\Thread;

/**
 * @mixin Thread
 */
final class ThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'board_id' => $this->board_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'is_hidden' => $this->is_hidden,
            'views' => $this->views,
            'score' => $this->score,
            'reply_count' => $this->reply_count,
            'last_post_id' => $this->last_post_id,
            'last_post_at' => $this->last_post_at?->toIso8601String(),
            'solution_post_id' => $this->solution_post_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'board' => BoardResource::make($this->whenLoaded('board')),
            'root_post' => PostResource::make($this->whenLoaded('rootPost')),
            'solution_post' => PostResource::make($this->whenLoaded('solutionPost')),
        ];
    }
}
