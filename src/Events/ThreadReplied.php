<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadReplied
{
    use Dispatchable;

    public function __construct(
        public readonly Thread $thread,
        public readonly Post $post,
    ) {}
}
