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
use Kurt\Modules\Forum\Listeners\AwardBadges;
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
        $commands = [
            RecountCommand::class,
            AwardBadgesCommand::class,
        ];

        // DemoCommand seeds via factories and bypasses InteractionManager, so
        // it is dev-only: never register it in production.
        if ($this->app->environment('local', 'testing')) {
            $commands[] = DemoCommand::class;
        }

        $package
            ->name('laravel-modules-forum')
            ->hasConfigFile('forum')
            ->hasTranslations()
            ->discoversMigrations()
            ->hasCommands($commands);
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

        // Only listen to the domain events the registered badge rules actually
        // target, instead of a global '*' wildcard that fires the awarder for
        // every framework event in the application.
        /** @var BadgeAwarder $awarder */
        $awarder = $this->app->make(BadgeAwarder::class);

        $badgeEvents = [];
        foreach ($awarder->rules() as $rule) {
            foreach ($rule->appliesAfter() as $eventClass) {
                $badgeEvents[$eventClass] = true;
            }
        }

        // AwardBadges is a ShouldQueue listener: awarding runs on the queue after
        // the caller's vote/reply transaction commits, so a badge failure can never
        // roll the user's action back.
        Event::listen(array_keys($badgeEvents), AwardBadges::class);

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Board::class, BoardPolicy::class);
        $gate->policy(Thread::class, ThreadPolicy::class);
        $gate->policy(Post::class, PostPolicy::class);
        $gate->policy(ModerationReport::class, ModerationReportPolicy::class);

        // Register the REST API surface. A no-op unless forum.http.mode is
        // api/ui, so the module stays headless by default.
        $this->registerModuleApi(__DIR__.'/../../routes/api.php');
    }
}
