<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
