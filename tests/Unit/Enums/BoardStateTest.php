<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\BoardState;

it('exposes the expected cases and values', function () {
    expect(BoardState::Open->value)->toBe('open');
    expect(BoardState::Locked->value)->toBe('locked');
    expect(BoardState::Archived->value)->toBe('archived');
    expect(BoardState::cases())->toHaveCount(3);
});
