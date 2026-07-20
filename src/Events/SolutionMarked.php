<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

/**
 * Dispatched when a thread's accepted answer (solution) is set.
 */
final class SolutionMarked
{
    use Dispatchable;

    public function __construct(
        public readonly Thread $thread,
        public readonly Post $post,
    ) {}
}
