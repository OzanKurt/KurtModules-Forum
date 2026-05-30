<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources\BadgeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Forum\Filament\V3\Resources\BadgeResource;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;
}
