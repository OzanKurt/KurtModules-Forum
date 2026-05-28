<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Forum\Badges\BadgeAwarder;
use Kurt\Modules\Forum\Badges\BadgeRule;
use Kurt\Modules\Forum\Events\BadgeAwarded;
use Kurt\Modules\Forum\Events\PostCreated;
use Kurt\Modules\Forum\Models\Badge;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Models\UserBadge;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'badger@example.com']);

    // Seed a Badge row that maps to the first-post slug.
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
        'user_id' => $this->user->id,
    ]);

    $this->post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $this->user->id,
        'body' => 'Hello world',
        'is_root' => true,
    ]);
});

it('awards a badge once and never re-awards it', function () {
    Event::fake([BadgeAwarded::class]);

    $awarder = app(BadgeAwarder::class);

    $awarder->handleEvent(new PostCreated($this->post));
    expect(UserBadge::query()->where('user_id', $this->user->id)->count())->toBe(1);

    // Dispatch again — should not duplicate.
    $awarder->handleEvent(new PostCreated($this->post));
    expect(UserBadge::query()->where('user_id', $this->user->id)->count())->toBe(1);

    Event::assertDispatchedTimes(BadgeAwarded::class, 1);
});

it('dispatches BadgeAwarded when the rule fires', function () {
    Event::fake([BadgeAwarded::class]);

    app(BadgeAwarder::class)->handleEvent(new PostCreated($this->post));

    Event::assertDispatched(BadgeAwarded::class, function (BadgeAwarded $event): bool {
        return $event->badge->slug === 'first-post';
    });
});

it('registers a custom rule via BadgeAwarder::register', function () {
    Badge::factory()->create([
        'slug' => 'custom-poster',
        'name' => ['en' => 'Custom'],
        'description' => ['en' => 'Custom rule fired.'],
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

    $awarder->handleEvent(new PostCreated($this->post));

    expect(
        UserBadge::query()
            ->where('user_id', $this->user->id)
            ->whereHas('badge', fn ($q) => $q->where('slug', 'custom-poster'))
            ->exists()
    )->toBeTrue();
});

it('skips when the configured Badge row is missing', function () {
    // Remove the seeded badge so the slug resolution returns null.
    Badge::query()->where('slug', 'first-post')->delete();

    app(BadgeAwarder::class)->handleEvent(new PostCreated($this->post));

    expect(UserBadge::query()->count())->toBe(0);
});
