<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Models\Vote;

final class DemoCommand extends Command
{
    protected $signature = 'forum:demo';

    protected $description = 'Seed demo boards, threads, posts, and votes.';

    public function handle(): int
    {
        $ownerId = (int) (DB::table('users')->value('id') ?? 1);

        /** @var Board $root */
        $root = Board::factory()->create();
        Board::factory()->child($root)->create();

        Thread::factory()
            ->count(3)
            ->state(fn () => ['board_id' => $root->id, 'user_id' => $ownerId])
            ->create()
            ->each(function (Thread $thread) use ($ownerId): void {
                /** @var Post $opPost */
                $opPost = Post::factory()->root()->create([
                    'thread_id' => $thread->id,
                    'user_id' => $ownerId,
                ]);

                Post::factory()->count(2)->create([
                    'thread_id' => $thread->id,
                    'user_id' => $ownerId,
                ]);

                Vote::factory()->create([
                    'post_id' => $opPost->id,
                    'user_id' => $ownerId,
                    'value' => VoteValue::Up->value,
                ]);
            });

        $this->info('Demo data seeded.');

        return self::SUCCESS;
    }
}
