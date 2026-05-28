<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\VoteCast;

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
        $upvotes = (int) DB::table('forum_votes')
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_votes.post_id')
            ->where('forum_posts.user_id', $user->getKey())
            ->where('forum_votes.value', VoteValue::Up->value)
            ->count();

        return $upvotes >= 100;
    }
}
