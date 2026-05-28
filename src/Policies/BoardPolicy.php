<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Enums\Visibility;
use Kurt\Modules\Forum\Models\Board;

final class BoardPolicy
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

    public function viewBoard(?Authenticatable $user, Board $board): bool
    {
        return match ($board->visibility) {
            Visibility::Public => true,
            Visibility::Unlisted => $user !== null,
            Visibility::Private => $user !== null,
        };
    }

    public function createThread(Authenticatable $user, Board $board): bool
    {
        return $board->state === BoardState::Open;
    }
}
