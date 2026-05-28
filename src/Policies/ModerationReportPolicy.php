<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Forum\Models\ModerationReport;

final class ModerationReportPolicy
{
    public function before(?Authenticatable $user, string $ability): ?bool
    {
        if ($user === null) {
            return null;
        }

        /** @var Gate $gate */
        $gate = app(Gate::class);
        if ($gate->forUser($user)->has('canModerateForum') && $gate->forUser($user)->allows('canModerateForum')) {
            return true;
        }

        return null;
    }

    public function viewAny(Authenticatable $user): bool
    {
        return false;
    }

    public function view(Authenticatable $user, ModerationReport $report): bool
    {
        return (int) $user->getAuthIdentifier() === $report->reporter_id;
    }

    public function moderate(Authenticatable $user, ModerationReport $report): bool
    {
        // Default: only the canModerateForum gate (handled in before()) can resolve.
        return false;
    }
}
