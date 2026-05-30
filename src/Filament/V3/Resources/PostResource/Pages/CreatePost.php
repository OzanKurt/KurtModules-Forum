<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Forum\Filament\V3\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
