<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadPinned
{
    use Dispatchable;

    public function __construct(
        public readonly Thread $thread,
        public readonly ?Model $moderator = null,
    ) {}
}
