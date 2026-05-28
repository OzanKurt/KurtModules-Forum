<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Observers;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Events\ThreadCreated;
use Kurt\Modules\Forum\Models\Board;
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
        // Skip if soft-deleting and the row is just hidden — the listing already
        // ignores trashed threads in our scopes. We decrement to stay symmetrical
        // with the listing.
        Board::query()
            ->whereKey($thread->board_id)
            ->where('thread_count', '>', 0)
            ->update(['thread_count' => DB::raw('thread_count - 1')]);
    }

    public function restored(Thread $thread): void
    {
        Board::query()
            ->whereKey($thread->board_id)
            ->update(['thread_count' => DB::raw('thread_count + 1')]);
    }
}
