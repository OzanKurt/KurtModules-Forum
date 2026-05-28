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
use Kurt\Modules\Forum\Models\Vote;

return [
    'edit_window_minutes' => 60,
    'thread_max_title_length' => 200,
    'post_max_body_length' => 30_000,
    'allow_self_vote' => false,

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
        'vote' => Vote::class,
    ],
];
