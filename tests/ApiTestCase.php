<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Tests;

use Illuminate\Foundation\Application;

/**
 * Test case for the REST API: forces `forum.http.mode = api` before the module
 * boots so ForumServiceProvider::registerModuleApi() registers the routes and
 * the `forum-api` throttle limiter.
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('forum.http.mode', 'api');
    }
}
