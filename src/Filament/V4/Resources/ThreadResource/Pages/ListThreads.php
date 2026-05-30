<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V4\Resources\ThreadResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Forum\Filament\V4\Resources\ThreadResource;

class ListThreads extends ListRecords
{
    protected static string $resource = ThreadResource::class;

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
