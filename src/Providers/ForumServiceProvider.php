<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Forum\Badges\BadgeAwarder;
use Kurt\Modules\Forum\Badges\BadgeRule;
use Kurt\Modules\Forum\Console\Commands\AwardBadgesCommand;
use Kurt\Modules\Forum\Console\Commands\DemoCommand;
use Kurt\Modules\Forum\Console\Commands\RecountCommand;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\ModerationReport;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Observers\PostObserver;
use Kurt\Modules\Forum\Observers\ThreadObserver;
use Kurt\Modules\Forum\Policies\BoardPolicy;
use Kurt\Modules\Forum\Policies\ModerationReportPolicy;
use Kurt\Modules\Forum\Policies\PostPolicy;
use Kurt\Modules\Forum\Policies\ThreadPolicy;
use Spatie\LaravelPackageTools\Package;

final class ForumServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'forum';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-forum')
            ->hasConfigFile('forum')
            ->hasTranslations()
            ->hasMigrations([
                'create_forum_boards_table',
                'create_forum_threads_table',
                'create_forum_posts_table',
                'create_forum_votes_table',
                'create_forum_subscriptions_table',
                'create_forum_moderation_reports_table',
                'create_forum_badges_table',
                'create_forum_user_badges_table',
            ])
            ->hasCommands([
                RecountCommand::class,
                AwardBadgesCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BadgeAwarder::class, function () {
            $awarder = new BadgeAwarder;

            /** @var array<int, class-string<BadgeRule>> $rules */
            $rules = (array) config('forum.badges.rules', []);

            foreach ($rules as $class) {
                /** @var BadgeRule $instance */
                $instance = $this->app->make($class);
                $awarder->register($instance);
            }

            return $awarder;
        });
    }

    public function packageBooted(): void
    {
        Post::observe(PostObserver::class);
        Thread::observe(ThreadObserver::class);

        Event::listen('*', function (string $name, array $payload): void {
            $event = $payload[0] ?? null;
            if (is_object($event)) {
                $this->app->make(BadgeAwarder::class)->handleEvent($event);
            }
        });

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Board::class, BoardPolicy::class);
        $gate->policy(Thread::class, ThreadPolicy::class);
        $gate->policy(Post::class, PostPolicy::class);
        $gate->policy(ModerationReport::class, ModerationReportPolicy::class);
    }
}
