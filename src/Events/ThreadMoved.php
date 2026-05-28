<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadMoved
{
    use Dispatchable;

    public function __construct(
        public readonly Thread $thread,
        public readonly Board $fromBoard,
        public readonly Board $toBoard,
        public readonly ?Model $moderator = null,
    ) {}
}
