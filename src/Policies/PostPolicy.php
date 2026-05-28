<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Kurt\Modules\Forum\Models\Post;

final class PostPolicy
{
    public function before(?Authenticatable $user, string $ability): ?bool
    {
        if ($user === null) {
            return null;
        }

        /** @var Gate $gate */
        $gate = app(Gate::class);
        if ($gate->forUser($user)->has('canModerateForum') && $gate->forUser($user)->allows('canModerateForum')) {
            return true;
        }

        return null;
    }

    public function editPost(Authenticatable $user, Post $post): bool
    {
        if ((int) $user->getAuthIdentifier() !== $post->user_id) {
            return false;
        }

        $window = (int) config('forum.edit_window_minutes', 60);
        $createdAt = $post->created_at;

        if (! $createdAt instanceof Carbon) {
            return false;
        }

        return $createdAt->gte(Carbon::now()->subMinutes($window));
    }

    public function deletePost(Authenticatable $user, Post $post): bool
    {
        return (int) $user->getAuthIdentifier() === $post->user_id;
    }

    public function votePost(Authenticatable $user, Post $post): bool
    {
        if ((bool) config('forum.allow_self_vote')) {
            return true;
        }

        return (int) $user->getAuthIdentifier() !== $post->user_id;
    }
}
