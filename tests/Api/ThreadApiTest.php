<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->stranger = StubUser::create(['email' => 'stranger@example.com']);
    $this->board = Board::factory()->create();
});

it('lists threads with pagination meta and excludes hidden threads', function () {
    Thread::factory()->count(3)->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
    ]);
    Thread::factory()->hidden()->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
    ]);

    $response = $this->getJson('/api/forum/threads?per_page=2')->assertOk();

    // 3 visible threads, 2 per page.
    expect($response->json('meta.pagination.total'))->toBe(3)
        ->and($response->json('meta.pagination.per_page'))->toBe(2)
        ->and($response->json('data'))->toHaveCount(2);
});

it('filters threads by board, author and solved state', function () {
    $otherBoard = Board::factory()->create();

    $mine = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
    Thread::factory()->create(['board_id' => $otherBoard->id, 'user_id' => $this->author->id]);
    Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->stranger->id]);

    // Mark $mine solved.
    $answer = Post::factory()->create(['thread_id' => $mine->id, 'user_id' => $this->stranger->id]);
    $mine->markSolution($answer);

    // by board
    $this->getJson('/api/forum/threads?filter[board]='.$this->board->id)
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // by author
    $this->getJson('/api/forum/threads?filter[author]='.$this->stranger->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    // solved only
    $solved = $this->getJson('/api/forum/threads?filter[solved]=1')->assertOk();
    expect($solved->json('data'))->toHaveCount(1)
        ->and($solved->json('data.0.id'))->toBe($mine->id);

    // unsolved only
    $this->getJson('/api/forum/threads?filter[solved]=0')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('sorts threads by replies, last_post and created_at', function () {
    $low = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id, 'reply_count' => 1]);
    $high = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id, 'reply_count' => 9]);

    $response = $this->getJson('/api/forum/threads?sort=-replies')->assertOk();

    expect($response->json('data.0.id'))->toBe($high->id)
        ->and($response->json('data.1.id'))->toBe($low->id);

    $asc = $this->getJson('/api/forum/threads?sort=replies')->assertOk();
    expect($asc->json('data.0.id'))->toBe($low->id);
});

it('shows a thread with its board and root post', function () {
    $thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
    Post::factory()->root()->create(['thread_id' => $thread->id, 'user_id' => $this->author->id]);

    $this->getJson('/api/forum/threads/'.$thread->id)
        ->assertOk()
        ->assertJsonPath('data.id', $thread->id)
        ->assertJsonPath('data.board.id', $this->board->id)
        ->assertJsonPath('data.root_post.is_root', true);
});

it('hides a hidden thread from show for non-moderators', function () {
    $thread = Thread::factory()->hidden()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);

    $this->getJson('/api/forum/threads/'.$thread->id)->assertForbidden();
});

it('creates a thread with a root post for an authenticated user', function () {
    $response = $this->actingAs($this->author)
        ->postJson('/api/forum/threads', [
            'board_id' => $this->board->id,
            'title' => 'How do I forum?',
            'body' => 'Please help.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'How do I forum?')
        ->assertJsonPath('data.user_id', $this->author->id);

    $threadId = $response->json('data.id');

    expect(Thread::query()->whereKey($threadId)->exists())->toBeTrue();

    $thread = Thread::query()->findOrFail($threadId);
    expect($thread->rootPost)->not->toBeNull()
        ->and($thread->rootPost->body)->toBe('Please help.')
        ->and($thread->rootPost->is_root)->toBeTrue();
});

it('validates the store payload', function () {
    $this->actingAs($this->author)
        ->postJson('/api/forum/threads', ['board_id' => $this->board->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'body']);
});

it('rejects creating a thread on a locked board', function () {
    $locked = Board::factory()->boardState(BoardState::Locked)->create();

    $this->actingAs($this->author)
        ->postJson('/api/forum/threads', [
            'board_id' => $locked->id,
            'title' => 'Nope',
            'body' => 'Nope',
        ])
        ->assertForbidden();
});

it('lets the author update their thread but forbids a stranger', function () {
    $thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);

    $this->actingAs($this->author)
        ->patchJson('/api/forum/threads/'.$thread->id, ['title' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Renamed');

    $this->actingAs($this->stranger)
        ->patchJson('/api/forum/threads/'.$thread->id, ['title' => 'Hijacked'])
        ->assertForbidden();
});

it('lets the author delete their thread but forbids a stranger', function () {
    $thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);

    $this->actingAs($this->stranger)
        ->deleteJson('/api/forum/threads/'.$thread->id)
        ->assertForbidden();

    $this->actingAs($this->author)
        ->deleteJson('/api/forum/threads/'.$thread->id)
        ->assertNoContent();

    expect($thread->fresh()->trashed())->toBeTrue();
});

it('blocks guests from thread writes with 401', function () {
    $thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);

    $this->postJson('/api/forum/threads', [])->assertUnauthorized();
    $this->patchJson('/api/forum/threads/'.$thread->id, ['title' => 'x'])->assertUnauthorized();
    $this->deleteJson('/api/forum/threads/'.$thread->id)->assertUnauthorized();
});
