<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;

interface BadgeRule
{
    /**
     * Slug of the Badge row this rule awards. Must match a row in `forum_badges.slug`.
     */
    public function badgeSlug(): string;

    /**
     * Event classes that should trigger evaluation of this rule.
     *
     * @return array<int, class-string>
     */
    public function appliesAfter(): array;

    /**
     * Return true if `$user` should be awarded the badge after `$event`.
     */
    public function evaluate(Model $user, object $event): bool;
}
