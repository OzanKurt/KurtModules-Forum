<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources\UserBadgeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Forum\Filament\V3\Resources\UserBadgeResource;

class ListUserBadges extends ListRecords
{
    protected static string $resource = UserBadgeResource::class;
}
