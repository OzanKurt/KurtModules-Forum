<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Forum\Events\ThreadCreated;
use Kurt\Modules\Forum\Models\Thread;

final class FirstThreadBadge implements BadgeRule
{
    public function badgeSlug(): string
    {
        return 'first-thread';
    }

    /**
     * @return array<int, class-string>
     */
    public function appliesAfter(): array
    {
        return [ThreadCreated::class];
    }

    public function evaluate(Model $user, object $event): bool
    {
        return Thread::query()->where('user_id', $user->getKey())->count() >= 1;
    }
}
