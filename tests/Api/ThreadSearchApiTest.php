<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->board = Board::factory()->create();
});

it('searches threads by title', function () {
    $match = Thread::factory()->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
        'title' => 'Postgres indexing strategies',
    ]);
    Thread::factory()->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
        'title' => 'Totally unrelated cooking recipes',
    ]);

    $response = $this->getJson('/api/forum/threads/search?q=Postgres')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

it('searches threads by post body', function () {
    $thread = Thread::factory()->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
        'title' => 'A generic title',
    ]);
    Post::factory()->root()->create([
        'thread_id' => $thread->id,
        'user_id' => $this->author->id,
        'body' => 'The answer involves a unicornword token.',
    ]);

    $response = $this->getJson('/api/forum/threads/search?q=unicornword')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($thread->id);
});

it('validates that a search term is required', function () {
    $this->getJson('/api/forum/threads/search')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('does not treat search as a thread id', function () {
    // Ensures the `threads/search` route is matched before `threads/{thread}`.
    $this->getJson('/api/forum/threads/search?q=anything')->assertOk();
});
