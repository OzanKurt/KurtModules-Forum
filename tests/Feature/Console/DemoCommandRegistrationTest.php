<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Console\Commands\AwardBadgesCommand;
use Kurt\Modules\Forum\Console\Commands\DemoCommand;
use Kurt\Modules\Forum\Console\Commands\RecountCommand;
use Kurt\Modules\Forum\Providers\ForumServiceProvider;
use Spatie\LaravelPackageTools\Package;

function configuredForumCommands(string $environment): array
{
    $app = app();
    $original = $app['env'];
    $app['env'] = $environment;

    try {
        $package = new Package;
        (new ForumServiceProvider($app))->configurePackage($package);

        return $package->commands;
    } finally {
        // Restore the env so the test-database teardown does not hit
        // production confirmation prompts.
        $app['env'] = $original;
    }
}

it('registers DemoCommand in local and testing environments', function (string $environment) {
    $commands = configuredForumCommands($environment);

    expect($commands)->toContain(DemoCommand::class);
    expect($commands)->toContain(RecountCommand::class);
    expect($commands)->toContain(AwardBadgesCommand::class);
})->with(['local', 'testing']);

it('does not register DemoCommand in production', function () {
    $commands = configuredForumCommands('production');

    expect($commands)->not->toContain(DemoCommand::class);
    // The safe, non-factory commands remain registered everywhere.
    expect($commands)->toContain(RecountCommand::class);
    expect($commands)->toContain(AwardBadgesCommand::class);
});
