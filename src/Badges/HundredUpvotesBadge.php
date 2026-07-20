<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\VoteCast;
use Kurt\Modules\Forum\Models\Post;

final class HundredUpvotesBadge implements BadgeRule
{
    public function badgeSlug(): string
    {
        return 'hundred-upvotes';
    }

    /**
     * @return array<int, class-string>
     */
    public function appliesAfter(): array
    {
        return [VoteCast::class];
    }

    public function evaluate(Model $user, object $event): bool
    {
        $upvotes = (int) DB::table('interactions_interactions')
            ->join('forum_posts', 'forum_posts.id', '=', 'interactions_interactions.subject_id')
            ->where('interactions_interactions.subject_type', (new Post)->getMorphClass())
            ->where('interactions_interactions.type', 'vote')
            ->where('interactions_interactions.value', VoteValue::Up->value)
            ->where('forum_posts.user_id', $user->getKey())
            ->count();

        return $upvotes >= 100;
    }
}
