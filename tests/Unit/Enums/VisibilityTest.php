<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\Visibility;

it('exposes the expected cases and values', function () {
    expect(Visibility::Public->value)->toBe('public');
    expect(Visibility::Unlisted->value)->toBe('unlisted');
    expect(Visibility::Private->value)->toBe('private');
    expect(Visibility::cases())->toHaveCount(3);
});
