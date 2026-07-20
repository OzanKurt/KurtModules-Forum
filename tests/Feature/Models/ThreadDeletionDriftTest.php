<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Support\ThreadCounters;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'drift-author@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    $this->board = $board;

    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $this->author->id,
    ]);
    $this->thread = $thread;

    // Root post (bumps post_count to 1 via PostObserver) + three replies.
    Post::create([
        'thread_id' => $thread->id,
        'user_id' => $this->author->id,
        'body' => 'Root',
        'is_root' => true,
    ]);
    $this->thread->reply($this->author, 'Reply 1');
    $this->thread->reply($this->author, 'Reply 2');
    $this->thread->reply($this->author, 'Reply 3');
});

it('keeps board.post_count matching recountBoard after a thread soft delete', function () {
    // Live counters before delete: 1 thread, 4 posts (root + 3 replies).
    expect($this->board->fresh()->thread_count)->toBe(1);
    expect($this->board->fresh()->post_count)->toBe(4);

    $this->thread->delete();

    $liveThreadCount = $this->board->fresh()->thread_count;
    $livePostCount = $this->board->fresh()->post_count;

    // Live counters must equal the authoritative recompute.
    ThreadCounters::recountBoard($this->board);
    $recomputed = $this->board->fresh();

    expect($liveThreadCount)->toBe($recomputed->thread_count)
        ->and($livePostCount)->toBe($recomputed->post_count)
        ->and($livePostCount)->toBe(0)
        ->and($liveThreadCount)->toBe(0);
});

it('restores board.post_count to match recountBoard after a thread restore', function () {
    $this->thread->delete();
    $this->thread->restore();

    $liveThreadCount = $this->board->fresh()->thread_count;
    $livePostCount = $this->board->fresh()->post_count;

    ThreadCounters::recountBoard($this->board);
    $recomputed = $this->board->fresh();

    expect($liveThreadCount)->toBe($recomputed->thread_count)
        ->and($livePostCount)->toBe($recomputed->post_count)
        ->and($livePostCount)->toBe(4)
        ->and($liveThreadCount)->toBe(1);
});

it('does not double-count replies already soft-deleted before the thread delete', function () {
    // Soft-delete one reply first (PostObserver drops post_count to 3), then the
    // thread. The board must not decrement for the already-removed reply.
    /** @var Post $reply */
    $reply = Post::query()->where('thread_id', $this->thread->id)->where('is_root', false)->first();
    $reply->delete();

    expect($this->board->fresh()->post_count)->toBe(3);

    $this->thread->delete();

    $livePostCount = $this->board->fresh()->post_count;

    ThreadCounters::recountBoard($this->board);

    expect($livePostCount)->toBe($this->board->fresh()->post_count)
        ->and($livePostCount)->toBe(0);
});
