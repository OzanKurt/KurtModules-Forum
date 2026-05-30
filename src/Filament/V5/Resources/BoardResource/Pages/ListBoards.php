<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources\BoardResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Forum\Filament\V5\Resources\BoardResource;

class ListBoards extends ListRecords
{
    protected static string $resource = BoardResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
