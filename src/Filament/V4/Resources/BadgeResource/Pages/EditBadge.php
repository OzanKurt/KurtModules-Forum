<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V4\Resources\BadgeResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Forum\Filament\V4\Resources\BadgeResource;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;

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
