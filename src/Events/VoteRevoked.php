<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Post;

final class VoteRevoked
{
    use Dispatchable;

    public function __construct(
        public readonly Post $post,
        public readonly Model $user,
    ) {}
}
