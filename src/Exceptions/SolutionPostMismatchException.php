<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Exceptions;

use InvalidArgumentException;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

/**
 * Thrown when a post from another thread is passed to Thread::markSolution().
 * A solution must be one of the thread's own posts.
 */
final class SolutionPostMismatchException extends InvalidArgumentException
{
    public static function for(Thread $thread, Post $post): self
    {
        return new self(
            "Post [{$post->id}] does not belong to thread [{$thread->id}] and cannot be its solution.",
        );
    }
}
