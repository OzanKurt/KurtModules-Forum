<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Observers;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Events\ThreadCreated;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadObserver
{
    public function created(Thread $thread): void
    {
        // Atomic increment of board.thread_count.
        Board::query()
            ->whereKey($thread->board_id)
            ->update(['thread_count' => DB::raw('thread_count + 1')]);

        ThreadCreated::dispatch($thread);
    }

    public function deleted(Thread $thread): void
    {
        // A trashed thread drops out of Board::recountBoard (which joins only
        // non-trashed threads), so its still-live posts must leave board.post_count
        // too — otherwise the live counter drifts above the recomputed value.
        // Decrement thread_count by 1 and post_count by the thread's live post
        // count (root + replies) so live == recountBoard after a soft delete.
        $posts = $this->livePostCount($thread);

        Board::query()
            ->whereKey($thread->board_id)
            ->where('thread_count', '>', 0)
            ->update(['thread_count' => DB::raw('thread_count - 1')]);

        if ($posts > 0) {
            Board::query()
                ->whereKey($thread->board_id)
                ->where('post_count', '>=', $posts)
                ->update(['post_count' => DB::raw('post_count - '.$posts)]);
        }
    }

    public function restored(Thread $thread): void
    {
        // Restoring re-admits the thread and its still-live posts to the board.
        $posts = $this->livePostCount($thread);

        Board::query()
            ->whereKey($thread->board_id)
            ->update([
                'thread_count' => DB::raw('thread_count + 1'),
                'post_count' => DB::raw('post_count + '.$posts),
            ]);
    }

    private function livePostCount(Thread $thread): int
    {
        // Post uses SoftDeletes, so the default scope already excludes trashed
        // rows — matching recountBoard's whereNull(deleted_at) join condition.
        return (int) Post::query()->where('thread_id', $thread->id)->count();
    }
}
