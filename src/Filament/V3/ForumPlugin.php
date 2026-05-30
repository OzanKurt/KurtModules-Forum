<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\Forum\Filament\V3\Resources\BadgeResource;
use Kurt\Modules\Forum\Filament\V3\Resources\BoardResource;
use Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource;
use Kurt\Modules\Forum\Filament\V3\Resources\PostResource;
use Kurt\Modules\Forum\Filament\V3\Resources\ThreadResource;
use Kurt\Modules\Forum\Filament\V3\Resources\UserBadgeResource;

final class ForumPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-forum';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BoardResource::class,
            ThreadResource::class,
            PostResource::class,
            ModerationReportResource::class,
            BadgeResource::class,
            UserBadgeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
