<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Kurt\Modules\Forum\Badges\BadgeAwarder;

/**
 * Runs the badge rules for a domain event on the queue, after the caller's
 * transaction commits. Because awarding no longer executes inside the vote/reply
 * transaction, a failed or contended award can never roll the user's action back.
 */
final class AwardBadges implements ShouldQueue
{
    /**
     * Dispatch the queued job only once the surrounding database transaction has
     * committed, so the awarder never observes (or is rolled back with) a
     * half-applied vote or reply.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly BadgeAwarder $awarder) {}

    public function handle(object $event): void
    {
        $this->awarder->handleEvent($event);
    }
}
