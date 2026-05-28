<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Models\Post;

final class FirstPostBadge implements BadgeRule
{
    public function badgeSlug(): string
    {
        return 'first-post';
    }

    /**
     * @return array<int, class-string>
     */
    public function appliesAfter(): array
    {
        return [PostCreated::class];
    }

    public function evaluate(Model $user, object $event): bool
    {
        return Post::query()->where('user_id', $user->getKey())->count() >= 1;
    }
}
