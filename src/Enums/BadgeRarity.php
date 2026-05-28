<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Enums;

enum BadgeRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Legendary = 'legendary';
}
