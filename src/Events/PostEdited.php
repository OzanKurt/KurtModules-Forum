<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\Post;

final class PostEdited
{
    use Dispatchable;

    public function __construct(public readonly Post $post) {}
}
