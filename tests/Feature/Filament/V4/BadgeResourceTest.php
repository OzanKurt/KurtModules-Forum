<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\V4\Resources\BadgeResource;
use Kurt\Modules\Forum\Filament\V4\Resources\BadgeResource\Pages\CreateBadge;
use Kurt\Modules\Forum\Filament\V4\Resources\BadgeResource\Pages\ListBadges;
use Kurt\Modules\Forum\Models\Badge;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament v4 is not installed.');
    }
});

it('targets the Badge model and registers its pages', function () {
    expect(BadgeResource::getModel())->toBe(Badge::class)
        ->and(array_keys(BadgeResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a translatable form with rarity, icon and is_active', function () {
    $fields = formFieldNames(BadgeResource::class, CreateBadge::class);

    expect($fields)
        ->toContain('name.en', 'name.tr')
        ->toContain('description.en', 'description.tr')
        ->toContain('slug', 'rarity', 'icon', 'is_active');
});

it('builds a table with rarity badge and active flag', function () {
    expect(tableColumnNames(BadgeResource::class, ListBadges::class))
        ->toContain('name', 'slug', 'rarity', 'is_active');

    expect(tableFilterNames(BadgeResource::class, ListBadges::class))
        ->toContain('rarity');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(BadgeResource::class, ListBadges::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(BadgeResource::class, ListBadges::class))
        ->toContain('delete');
});
