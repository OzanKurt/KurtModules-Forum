<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource;
use Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource\Pages\ListUserBadges;
use Kurt\Modules\Forum\Models\UserBadge;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('targets the UserBadge model and registers a read-mostly list + view page', function () {
    expect(UserBadgeResource::getModel())->toBe(UserBadge::class)
        ->and(array_keys(UserBadgeResource::getPages()))->toBe(['index', 'view']);
});

it('builds a table of awarded badges with a badge filter', function () {
    expect(tableColumnNames(UserBadgeResource::class, ListUserBadges::class))
        ->toContain('user.name', 'badge.name', 'awarded_at');

    expect(tableFilterNames(UserBadgeResource::class, ListUserBadges::class))
        ->toContain('badge_id');
});

it('exposes a view row action and bulk delete', function () {
    expect(tableActionNames(UserBadgeResource::class, ListUserBadges::class))
        ->toContain('view');

    expect(tableBulkActionNames(UserBadgeResource::class, ListUserBadges::class))
        ->toContain('delete');
});
