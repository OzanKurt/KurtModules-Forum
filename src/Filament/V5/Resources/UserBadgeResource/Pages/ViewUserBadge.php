<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource;

class ViewUserBadge extends ViewRecord
{
    protected static string $resource = UserBadgeResource::class;
}
