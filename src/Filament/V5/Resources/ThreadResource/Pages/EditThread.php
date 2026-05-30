<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource;

class EditThread extends EditRecord
{
    protected static string $resource = ThreadResource::class;

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
