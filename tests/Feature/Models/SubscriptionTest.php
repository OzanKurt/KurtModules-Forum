<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Forum\Events\SubscriptionCreated;
use Kurt\Modules\Forum\Events\SubscriptionRemoved;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Subscription;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'sub@example.com']);
    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $this->user->id,
    ]);
    $this->thread = $thread;
});

it('subscribes a user to a thread idempotently', function () {
    Event::fake([SubscriptionCreated::class]);

    $first = $this->thread->subscribe($this->user);
    $second = $this->thread->subscribe($this->user);

    expect($first->id)->toBe($second->id);
    expect(Subscription::query()->count())->toBe(1);

    Event::assertDispatchedTimes(SubscriptionCreated::class, 1);
});

it('unsubscribes a user and fires SubscriptionRemoved', function () {
    Event::fake([SubscriptionRemoved::class]);

    $this->thread->subscribe($this->user);

    $removed = $this->thread->unsubscribe($this->user);

    expect($removed)->toBeTrue();
    expect(Subscription::query()->count())->toBe(0);

    Event::assertDispatchedTimes(SubscriptionRemoved::class, 1);
});

it('returns false when unsubscribe is called on a non-subscriber', function () {
    expect($this->thread->unsubscribe($this->user))->toBeFalse();
});
