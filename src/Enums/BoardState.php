<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Enums;

enum BoardState: string
{
    case Open = 'open';
    case Locked = 'locked';
    case Archived = 'archived';
}
