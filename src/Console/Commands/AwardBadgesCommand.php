<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Contracts\UserResolver;
use Kurt\Modules\Forum\Badges\BadgeAwarder;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Events\ThreadCreated;
use Kurt\Modules\Forum\Events\VoteCast;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class AwardBadgesCommand extends Command
{
    protected $signature = 'forum:award-badges {--user= : User id to re-evaluate; omitted = all users}';

    protected $description = 'Replay PostCreated, ThreadCreated, VoteCast events through BadgeAwarder for a user (or all users).';

    public function handle(BadgeAwarder $awarder, UserResolver $resolver): int
    {
        $userKey = $this->option('user');
        $query = $resolver->newQuery();

        if ($userKey !== null && $userKey !== '') {
            $query->whereKey($userKey);
        }

        $count = 0;

        $query->chunkById(200, function ($users) use ($awarder, &$count): void {
            foreach ($users as $user) {
                /** @var Model $user */
                $this->replayThreadCreated($user, $awarder);
                $this->replayPostCreated($user, $awarder);
                $this->replayVoteCast($user, $awarder);
                $count++;
            }
        });

        $this->info("Re-evaluated badges for {$count} user(s).");

        return self::SUCCESS;
    }

    private function replayThreadCreated(Model $user, BadgeAwarder $awarder): void
    {
        $threadIds = DB::table('forum_threads')
            ->where('user_id', $user->getKey())
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($threadIds as $id) {
            /** @var Thread|null $thread */
            $thread = Thread::query()->find($id);
            if ($thread instanceof Thread) {
                $awarder->handleEvent(new ThreadCreated($thread));
            }
        }
    }

    private function replayPostCreated(Model $user, BadgeAwarder $awarder): void
    {
        $postIds = DB::table('forum_posts')
            ->where('user_id', $user->getKey())
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($postIds as $id) {
            /** @var Post|null $post */
            $post = Post::query()->find($id);
            if ($post instanceof Post) {
                $awarder->handleEvent(new PostCreated($post));
            }
        }
    }

    private function replayVoteCast(Model $user, BadgeAwarder $awarder): void
    {
        // For Hundred-Upvotes: votes on posts authored by $user.
        $votes = DB::table('interactions_interactions')
            ->join('forum_posts', 'forum_posts.id', '=', 'interactions_interactions.subject_id')
            ->where('interactions_interactions.subject_type', Post::class)
            ->where('interactions_interactions.type', 'vote')
            ->where('forum_posts.user_id', $user->getKey())
            ->get(['interactions_interactions.subject_id as post_id', 'interactions_interactions.value as value']);

        foreach ($votes as $row) {
            /** @var Post|null $post */
            $post = Post::query()->find($row->post_id);
            if ($post instanceof Post) {
                $awarder->handleEvent(new VoteCast($post, VoteValue::from((int) $row->value)));
            }
        }
    }
}
