<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\V3\Resources\BoardResource;
use Kurt\Modules\Forum\Filament\V3\Resources\BoardResource\Pages\CreateBoard;
use Kurt\Modules\Forum\Filament\V3\Resources\BoardResource\Pages\ListBoards;
use Kurt\Modules\Forum\Models\Board;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament v3 is not installed.');
    }
});

it('targets the Board model and registers its pages', function () {
    expect(BoardResource::getModel())->toBe(Board::class)
        ->and(array_keys(BoardResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a translatable form with state, visibility and parent fields', function () {
    $fields = formFieldNames(BoardResource::class, CreateBoard::class);

    expect($fields)
        ->toContain('name.en', 'name.tr')
        ->toContain('description.en', 'description.tr')
        ->toContain('state', 'visibility', 'parent_id', 'position');
});

it('builds a table with state and visibility badges and counters', function () {
    expect(tableColumnNames(BoardResource::class, ListBoards::class))
        ->toContain('name', 'state', 'visibility', 'parent.name', 'thread_count', 'post_count');

    expect(tableFilterNames(BoardResource::class, ListBoards::class))
        ->toContain('state', 'visibility');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(BoardResource::class, ListBoards::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(BoardResource::class, ListBoards::class))
        ->toContain('delete');
});
