<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource;

class CreateThread extends CreateRecord
{
    protected static string $resource = ThreadResource::class;
}
