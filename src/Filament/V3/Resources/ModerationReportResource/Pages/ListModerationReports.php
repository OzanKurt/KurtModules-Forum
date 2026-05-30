<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource;

class ListModerationReports extends ListRecords
{
    protected static string $resource = ModerationReportResource::class;
}
