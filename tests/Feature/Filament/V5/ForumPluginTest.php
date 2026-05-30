<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\ForumPlugin;
use Kurt\Modules\Forum\Filament\V5\Resources\BadgeResource;
use Kurt\Modules\Forum\Filament\V5\Resources\BoardResource;
use Kurt\Modules\Forum\Filament\V5\Resources\ModerationReportResource;
use Kurt\Modules\Forum\Filament\V5\Resources\PostResource;
use Kurt\Modules\Forum\Filament\V5\Resources\ThreadResource;
use Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('dispatches the facade to the v5 plugin', function () {
    expect(ForumPlugin::make())->toBeInstanceOf(Kurt\Modules\Forum\Filament\V5\ForumPlugin::class)
        ->and(ForumPlugin::make()->getId())->toBe('kurtmodules-forum');
});

it('registers all six forum resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(BoardResource::class)
        ->toContain(ThreadResource::class)
        ->toContain(PostResource::class)
        ->toContain(ModerationReportResource::class)
        ->toContain(BadgeResource::class)
        ->toContain(UserBadgeResource::class);
});

it('registers routes for every resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/boards', 'admin/boards/create', 'admin/boards/{record}/edit')
        ->toContain('admin/threads', 'admin/posts')
        ->toContain('admin/moderation-reports', 'admin/moderation-reports/{record}/edit')
        ->toContain('admin/badges', 'admin/user-badges', 'admin/user-badges/{record}');
});
