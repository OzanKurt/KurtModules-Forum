<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V4\Resources\BoardResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Forum\Filament\V4\Resources\BoardResource;

class EditBoard extends EditRecord
{
    protected static string $resource = BoardResource::class;

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
