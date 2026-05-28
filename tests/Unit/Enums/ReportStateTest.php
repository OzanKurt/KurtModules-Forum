<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\ReportState;

it('exposes the expected cases and values', function () {
    expect(ReportState::Pending->value)->toBe('pending');
    expect(ReportState::Resolved->value)->toBe('resolved');
    expect(ReportState::Dismissed->value)->toBe('dismissed');
    expect(ReportState::cases())->toHaveCount(3);
});
