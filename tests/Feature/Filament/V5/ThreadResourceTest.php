<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource\Pages\CreateThread;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource\Pages\ListThreads;
use Kurt\Modules\Forum\Models\Thread;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('targets the Thread model and registers its pages', function () {
    expect(ThreadResource::getModel())->toBe(Thread::class)
        ->and(array_keys(ThreadResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a form with title, board and moderation toggles', function () {
    $fields = formFieldNames(ThreadResource::class, CreateThread::class);

    expect($fields)
        ->toContain('title', 'board_id', 'score')
        ->toContain('is_pinned', 'is_locked', 'is_hidden');
});

it('builds a table with moderation toggle columns and a board filter', function () {
    expect(tableColumnNames(ThreadResource::class, ListThreads::class))
        ->toContain('title', 'board.name', 'is_pinned', 'is_locked', 'is_hidden', 'reply_count', 'score');

    expect(tableFilterNames(ThreadResource::class, ListThreads::class))
        ->toContain('board_id', 'is_pinned', 'is_locked', 'is_hidden');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(ThreadResource::class, ListThreads::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(ThreadResource::class, ListThreads::class))
        ->toContain('delete');
});
