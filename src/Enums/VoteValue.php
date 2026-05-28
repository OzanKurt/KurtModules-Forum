<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Enums;

enum VoteValue: int
{
    case Down = -1;
    case Up = 1;
}
