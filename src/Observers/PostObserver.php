<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Observers;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Events\PostDeleted;
use Kurt\Modules\Forum\Events\PostEdited;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class PostObserver
{
    public function created(Post $post): void
    {
        // For non-root posts the Thread::reply() flow already updates board.post_count
        // inside its transaction; only bump the counter here for root posts created
        // outside Thread::reply (typical when a Thread is seeded by hand in tests).
        if ($post->is_root) {
            /** @var Thread|null $thread */
            $thread = Thread::query()->find($post->thread_id);
            if ($thread !== null) {
                Board::query()
                    ->whereKey($thread->board_id)
                    ->update(['post_count' => DB::raw('post_count + 1')]);
            }
        }

        PostCreated::dispatch($post);
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged('body')) {
            PostEdited::dispatch($post);
        }
    }

    public function deleted(Post $post): void
    {
        // Decrement board.post_count when a post leaves the listing.
        /** @var Thread|null $thread */
        $thread = Thread::query()->find($post->thread_id);
        if ($thread !== null) {
            Board::query()
                ->whereKey($thread->board_id)
                ->where('post_count', '>', 0)
                ->update(['post_count' => DB::raw('post_count - 1')]);

            if (! $post->is_root && $thread->reply_count > 0) {
                $thread->forceFill(['reply_count' => $thread->reply_count - 1])->save();
            }
        }

        PostDeleted::dispatch($post);
    }

    public function restored(Post $post): void
    {
        /** @var Thread|null $thread */
        $thread = Thread::query()->find($post->thread_id);
        if ($thread !== null) {
            Board::query()
                ->whereKey($thread->board_id)
                ->update(['post_count' => DB::raw('post_count + 1')]);

            if (! $post->is_root) {
                $thread->forceFill(['reply_count' => $thread->reply_count + 1])->save();
            }
        }
    }
}
