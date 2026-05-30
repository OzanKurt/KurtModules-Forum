<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\ModerationReportResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Forum\Filament\V5\Resources\ModerationReportResource;

class ListModerationReports extends ListRecords
{
    protected static string $resource = ModerationReportResource::class;
}
