<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\VoteValue;

it('exposes the expected cases and integer values', function () {
    expect(VoteValue::Down->value)->toBe(-1);
    expect(VoteValue::Up->value)->toBe(1);
    expect(VoteValue::cases())->toHaveCount(2);
});
