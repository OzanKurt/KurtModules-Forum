<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Badge;
use Kurt\Modules\Forum\Models\UserBadge;

final class BadgeAwarded
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly Badge $badge,
        public readonly UserBadge $award,
    ) {}
}
