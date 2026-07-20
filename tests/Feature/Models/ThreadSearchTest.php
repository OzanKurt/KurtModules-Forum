<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'searcher@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    $this->board = $board;
});

function makeThread(Board $board, StubUser $user, string $title): Thread
{
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $user->id,
        'title' => $title,
        'slug' => str($title)->slug()->append('-'.uniqid())->toString(),
    ]);

    return $thread;
}

it('matches threads by title', function () {
    $match = makeThread($this->board, $this->user, 'How to configure Redis caching');
    makeThread($this->board, $this->user, 'Unrelated topic about weather');

    $ids = Thread::query()->search('Redis')->pluck('id')->all();

    expect($ids)->toBe([$match->id]);
});

it('matches threads by a post body', function () {
    $match = makeThread($this->board, $this->user, 'General discussion');
    makeThread($this->board, $this->user, 'Another thread');

    Post::factory()->create([
        'thread_id' => $match->id,
        'user_id' => $this->user->id,
        'body' => 'You should try enabling the opcache extension for a speed boost.',
    ]);

    $ids = Thread::query()->search('opcache')->pluck('id')->all();

    expect($ids)->toBe([$match->id]);
});

it('returns no threads when nothing matches', function () {
    makeThread($this->board, $this->user, 'How to configure Redis caching');

    expect(Thread::query()->search('nonexistentterm')->count())->toBe(0);
});

it('returns an empty result for a blank term', function () {
    makeThread($this->board, $this->user, 'Any thread at all');

    expect(Thread::query()->search('   ')->count())->toBe(0);
});

it('returns each matching thread once even with multiple matching posts', function () {
    $thread = makeThread($this->board, $this->user, 'Kubernetes questions');

    foreach (range(1, 3) as $i) {
        Post::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $this->user->id,
            'body' => "Post {$i} talking about kubernetes networking",
        ]);
    }

    $ids = Thread::query()->search('kubernetes')->pluck('id')->all();

    expect($ids)->toBe([$thread->id]);
});

it('ranks title matches above body-only matches', function () {
    $bodyOnly = makeThread($this->board, $this->user, 'A plain thread');
    Post::factory()->create([
        'thread_id' => $bodyOnly->id,
        'user_id' => $this->user->id,
        'body' => 'This mentions laravel deep in the body.',
    ]);

    $titleMatch = makeThread($this->board, $this->user, 'Laravel best practices');

    $ids = Thread::query()->search('laravel')->pluck('id')->all();

    expect($ids)->toBe([$titleMatch->id, $bodyOnly->id]);
});
