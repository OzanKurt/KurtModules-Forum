<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Exceptions;

use Kurt\Modules\Forum\Models\Thread;
use RuntimeException;

/**
 * Thrown when a reply is attempted against a locked thread. Locking is a
 * moderation state that closes a thread to further replies, so Thread::reply()
 * rejects it explicitly rather than silently writing a post.
 */
final class ThreadLockedException extends RuntimeException
{
    public static function for(Thread $thread): self
    {
        return new self("Thread [{$thread->id}] is locked and cannot receive replies.");
    }
}
