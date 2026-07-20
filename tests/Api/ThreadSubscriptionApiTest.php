<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Subscription;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'user@example.com']);
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->board = Board::factory()->create();
    $this->thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
});

it('subscribes an authenticated user to a thread', function () {
    $this->actingAs($this->user)
        ->postJson('/api/forum/threads/'.$this->thread->id.'/subscribe')
        ->assertCreated()
        ->assertJsonPath('data.subscribed', true);

    expect(Subscription::query()
        ->where('user_id', $this->user->id)
        ->where('subscribable_type', Thread::class)
        ->where('subscribable_id', $this->thread->id)
        ->exists())->toBeTrue();
});

it('is idempotent when subscribing twice', function () {
    $this->actingAs($this->user)->postJson('/api/forum/threads/'.$this->thread->id.'/subscribe')->assertCreated();
    $this->actingAs($this->user)->postJson('/api/forum/threads/'.$this->thread->id.'/subscribe')->assertCreated();

    expect(Subscription::query()->where('subscribable_id', $this->thread->id)->count())->toBe(1);
});

it('unsubscribes a user from a thread', function () {
    $this->thread->subscribe($this->user);

    $this->actingAs($this->user)
        ->deleteJson('/api/forum/threads/'.$this->thread->id.'/subscribe')
        ->assertNoContent();

    expect(Subscription::query()->where('subscribable_id', $this->thread->id)->count())->toBe(0);
});

it('blocks guests from subscription endpoints with 401', function () {
    $this->postJson('/api/forum/threads/'.$this->thread->id.'/subscribe')->assertUnauthorized();
    $this->deleteJson('/api/forum/threads/'.$this->thread->id.'/subscribe')->assertUnauthorized();
});
