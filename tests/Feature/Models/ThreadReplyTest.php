<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Forum\Events\ThreadReplied;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->replier = StubUser::create(['email' => 'replier@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    $this->board = $board;

    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $this->author->id,
    ]);
    $this->thread = $thread;
});

it('increments reply_count, board.post_count, and last_post_at atomically', function () {
    expect($this->thread->reply_count)->toBe(0);
    expect($this->board->fresh()->post_count)->toBe(0);

    $this->thread->reply($this->replier, 'First reply');
    $this->thread->reply($this->replier, 'Second reply');
    $this->thread->reply($this->replier, 'Third reply');

    $fresh = $this->thread->fresh();
    expect($fresh->reply_count)->toBe(3);
    expect($fresh->last_post_at)->not->toBeNull();

    expect($this->board->fresh()->post_count)->toBe(3);
});

it('points last_post_id at the newly-created reply', function () {
    $reply = $this->thread->reply($this->replier, 'Hello');

    expect($this->thread->fresh()->last_post_id)->toBe($reply->id);
    expect($reply->is_root)->toBeFalse();
});

it('dispatches ThreadReplied with the fresh thread and new post', function () {
    Event::fake([ThreadReplied::class]);

    $reply = $this->thread->reply($this->replier, 'Pinged');

    Event::assertDispatched(ThreadReplied::class, function (ThreadReplied $event) use ($reply): bool {
        return $event->post->id === $reply->id
            && $event->thread->id === $this->thread->id;
    });
});

it('returns a Post created with the given parent', function () {
    $first = $this->thread->reply($this->replier, 'Root reply');
    $nested = $this->thread->reply($this->replier, 'Nested', $first);

    expect($nested->parent_id)->toBe($first->id);
});

it('produces the same counters under sequential concurrent transactions', function () {
    foreach (range(1, 5) as $i) {
        $this->thread->reply($this->replier, "Reply {$i}");
    }

    expect($this->thread->fresh()->reply_count)->toBe(5);
    expect(Post::query()->where('thread_id', $this->thread->id)->count())->toBe(5);
    expect($this->board->fresh()->post_count)->toBe(5);
});
