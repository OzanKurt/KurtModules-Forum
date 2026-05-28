<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Support\ThreadCounters;

final class RecountCommand extends Command
{
    protected $signature = 'forum:recount';

    protected $description = 'Rebuild denormalised counters (Board.thread_count, Board.post_count, Thread.reply_count, Post.score) from raw rows.';

    public function handle(): int
    {
        $posts = 0;
        Post::query()->chunkById(500, function ($chunk) use (&$posts): void {
            foreach ($chunk as $post) {
                /** @var Post $post */
                ThreadCounters::recountPost($post);
                $posts++;
            }
        });

        $threads = 0;
        Thread::query()->chunkById(500, function ($chunk) use (&$threads): void {
            foreach ($chunk as $thread) {
                /** @var Thread $thread */
                ThreadCounters::recount($thread);
                $threads++;
            }
        });

        $boards = 0;
        Board::query()->chunkById(500, function ($chunk) use (&$boards): void {
            foreach ($chunk as $board) {
                /** @var Board $board */
                ThreadCounters::recountBoard($board);
                $boards++;
            }
        });

        $this->info("Recounted {$boards} board(s), {$threads} thread(s), {$posts} post(s).");

        return self::SUCCESS;
    }
}
