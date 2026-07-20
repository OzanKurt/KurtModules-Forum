<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadPolicy
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

    public function viewThread(?Authenticatable $user, Thread $thread): bool
    {
        if ($thread->is_hidden) {
            return false;
        }

        return true;
    }

    public function replyToThread(Authenticatable $user, Thread $thread): bool
    {
        return ! $thread->is_locked && ! $thread->is_hidden;
    }

    /**
     * Editing a thread is limited to its author. Moderators are already granted
     * everything by before() via the `canModerateForum` gate.
     */
    public function updateThread(Authenticatable $user, Thread $thread): bool
    {
        return (int) $user->getAuthIdentifier() === $thread->user_id;
    }

    public function deleteThread(Authenticatable $user, Thread $thread): bool
    {
        return $this->updateThread($user, $thread);
    }

    /**
     * Marking/clearing the accepted answer is limited to the thread author.
     * Moderators are already granted everything by before() via the
     * `canModerateForum` gate.
     */
    public function markSolution(Authenticatable $user, Thread $thread): bool
    {
        return (int) $user->getAuthIdentifier() === $thread->user_id;
    }

    public function unmarkSolution(Authenticatable $user, Thread $thread): bool
    {
        return $this->markSolution($user, $thread);
    }
}
