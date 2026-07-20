<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Kurt\Modules\Forum\Badges\BadgeAwarder;
use Kurt\Modules\Forum\Badges\BadgeRule;
use Kurt\Modules\Forum\Events\BadgeAwarded;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Listeners\AwardBadges;
use Kurt\Modules\Forum\Models\Badge;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Models\UserBadge;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

it('awards a badge once and never re-awards it', function () {
    $user = StubUser::create(['email' => 'badger@example.com']);

    Badge::factory()->create([
        'slug' => 'first-post',
        'name' => ['en' => 'First Post'],
        'description' => ['en' => 'Posted for the first time.'],
    ]);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    // Reset awards from the in-place PostCreated dispatch.
    UserBadge::query()->where('user_id', $user->id)->delete();

    $awarder = app(BadgeAwarder::class);
    $awarder->handleEvent(new PostCreated($post));
    expect(UserBadge::query()->where('user_id', $user->id)->count())->toBe(1);

    // Dispatch again — should not duplicate.
    $awarder->handleEvent(new PostCreated($post));
    expect(UserBadge::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('dispatches BadgeAwarded when the rule fires', function () {
    Event::fake([BadgeAwarded::class]);

    $user = StubUser::create(['email' => 'event@example.com']);

    Badge::factory()->create([
        'slug' => 'first-post',
        'name' => ['en' => 'First Post'],
        'description' => ['en' => 'Posted for the first time.'],
    ]);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    // Faked above — PostCreated dispatched inside Post::create() goes through the
    // BadgeAwarder via the wildcard listener bound in ForumServiceProvider.
    Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    Event::assertDispatched(BadgeAwarded::class, function (BadgeAwarded $event) use ($user): bool {
        return $event->badge->slug === 'first-post'
            && $event->user->getKey() === $user->getKey();
    });
});

it('queues the AwardBadges listener for badge events instead of running it inline', function () {
    Queue::fake();

    $user = StubUser::create(['email' => 'queued@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    // Post::create dispatches PostCreated, which the provider routes to the
    // ShouldQueue AwardBadges listener. Being queued (rather than run inline in
    // the caller's transaction) is what keeps a badge failure from rolling the
    // user's action back.
    Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
        return $job->class === AwardBadges::class;
    });
});

it('is idempotent under a concurrent duplicate award (firstOrCreate, no throw)', function () {
    $user = StubUser::create(['email' => 'concurrent@example.com']);

    $badge = Badge::factory()->create([
        'slug' => 'first-post',
        'name' => ['en' => 'First Post'],
        'description' => ['en' => 'Posted for the first time.'],
    ]);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    // Simulate a racing award landing first, then run the awarder: firstOrCreate
    // must resolve to the existing row instead of raising a unique violation.
    UserBadge::query()->firstOrCreate(
        ['user_id' => $user->id, 'badge_id' => $badge->id],
        ['awarded_at' => now()],
    );

    $awarder = app(BadgeAwarder::class);
    $awarder->handleEvent(new PostCreated($post));

    expect(UserBadge::query()->where('user_id', $user->id)->where('badge_id', $badge->id)->count())->toBe(1);
});

it('registers a custom rule via BadgeAwarder::register', function () {
    $user = StubUser::create(['email' => 'custom@example.com']);

    Badge::factory()->create([
        'slug' => 'custom-poster',
        'name' => ['en' => 'Custom'],
        'description' => ['en' => 'Custom rule fired.'],
    ]);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    $awarder = app(BadgeAwarder::class);
    $awarder->register(new class implements BadgeRule
    {
        public function badgeSlug(): string
        {
            return 'custom-poster';
        }

        public function appliesAfter(): array
        {
            return [PostCreated::class];
        }

        public function evaluate(Model $user, object $event): bool
        {
            return true;
        }
    });

    $awarder->handleEvent(new PostCreated($post));

    expect(
        UserBadge::query()
            ->where('user_id', $user->id)
            ->whereHas('badge', fn ($q) => $q->where('slug', 'custom-poster'))
            ->exists()
    )->toBeTrue();
});

it('skips when the configured Badge row is missing', function () {
    $user = StubUser::create(['email' => 'no-badge@example.com']);

    // No Badge row for 'first-post' is seeded.
    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);

    app(BadgeAwarder::class)->handleEvent(new PostCreated($post));

    expect(UserBadge::query()->where('user_id', $user->id)->count())->toBe(0);
});
