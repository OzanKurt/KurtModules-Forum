<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Badges\FirstPostBadge;
use Kurt\Modules\Forum\Badges\FirstThreadBadge;
use Kurt\Modules\Forum\Badges\HundredUpvotesBadge;
use Kurt\Modules\Forum\Badges\TenPostsBadge;
use Kurt\Modules\Forum\Badges\WelcomeCommitterBadge;
use Kurt\Modules\Forum\Models\Badge;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\ModerationReport;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Subscription;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Models\UserBadge;

return [
    'edit_window_minutes' => 60,
    'thread_max_title_length' => 200,
    'post_max_body_length' => 30_000,
    'allow_self_vote' => false,

    /*
    |--------------------------------------------------------------------------
    | HTTP / REST API
    |--------------------------------------------------------------------------
    |
    | Drives the out-of-the-box JSON API (Core's API kit). Safe by default:
    | `headless` registers nothing. Set FORUM_HTTP_MODE=api (or `ui`) to expose
    | the endpoints. Read routes are public; write routes get `auth_middleware`
    | and are policy-guarded. Every route is throttled by the `forum-api`
    | limiter built from `rate_limit` ("maxAttempts,decayMinutes").
    |
    */
    'http' => [
        'mode' => env('FORUM_HTTP_MODE', 'headless'),
        'prefix' => 'api/forum',
        'middleware' => ['api'],
        'auth_middleware' => ['auth'],
        'rate_limit' => '60,1',
    ],

    'media' => [
        'disk' => env('FORUM_MEDIA_DISK', 'public'),
    ],

    'badges' => [
        'rules' => [
            FirstPostBadge::class,
            TenPostsBadge::class,
            HundredUpvotesBadge::class,
            FirstThreadBadge::class,
            WelcomeCommitterBadge::class,
        ],
    ],

    'models' => [
        'badge' => Badge::class,
        'board' => Board::class,
        'moderation_report' => ModerationReport::class,
        'post' => Post::class,
        'subscription' => Subscription::class,
        'thread' => Thread::class,
        'user_badge' => UserBadge::class,
    ],
];
