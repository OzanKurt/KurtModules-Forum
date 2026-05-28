<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Events\ThreadCreated;

/**
 * Awarded the first time we observe activity from a user whose account is at
 * least one year old.
 */
final class WelcomeCommitterBadge implements BadgeRule
{
    public function badgeSlug(): string
    {
        return 'welcome-committer';
    }

    /**
     * @return array<int, class-string>
     */
    public function appliesAfter(): array
    {
        return [PostCreated::class, ThreadCreated::class];
    }

    public function evaluate(Model $user, object $event): bool
    {
        /** @var Carbon|null $createdAt */
        $createdAt = $user->getAttribute('created_at');

        if (! $createdAt instanceof Carbon) {
            return false;
        }

        return $createdAt->lte(Carbon::now()->subYear());
    }
}
