<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource;

class EditModerationReport extends EditRecord
{
    protected static string $resource = ModerationReportResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
