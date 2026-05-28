<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Models\Board;

it('lists only open boards via scopeOpen', function () {
    Board::factory()->boardState(BoardState::Open)->create();
    Board::factory()->boardState(BoardState::Locked)->create();
    Board::factory()->boardState(BoardState::Archived)->create();

    expect(Board::query()->open()->count())->toBe(1);
});

it('lists only root boards via scopeRoots', function () {
    $root = Board::factory()->create();
    Board::factory()->child($root)->create();

    expect(Board::query()->roots()->count())->toBe(1);
    expect(Board::query()->roots()->first()->id)->toBe($root->id);
});

it('persists translatable name/description as JSON', function () {
    /** @var Board $board */
    $board = Board::factory()->create([
        'name' => ['en' => 'General', 'tr' => 'Genel'],
        'description' => ['en' => 'Catch all'],
    ]);

    $fresh = $board->fresh();
    expect($fresh->getTranslation('name', 'en'))->toBe('General');
    expect($fresh->getTranslation('name', 'tr'))->toBe('Genel');
});
