<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\BadgeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Forum\Filament\V5\Resources\BadgeResource;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;
}
