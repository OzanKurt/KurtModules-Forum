<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\BadgeRarity;

it('exposes the expected cases and values', function () {
    expect(BadgeRarity::Common->value)->toBe('common');
    expect(BadgeRarity::Uncommon->value)->toBe('uncommon');
    expect(BadgeRarity::Rare->value)->toBe('rare');
    expect(BadgeRarity::Legendary->value)->toBe('legendary');
    expect(BadgeRarity::cases())->toHaveCount(4);
});
