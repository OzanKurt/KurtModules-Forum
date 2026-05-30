<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V4\Resources\BoardResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Forum\Filament\V4\Resources\BoardResource;

class CreateBoard extends CreateRecord
{
    protected static string $resource = BoardResource::class;
}
