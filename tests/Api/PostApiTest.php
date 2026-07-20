<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->replier = StubUser::create(['email' => 'replier@example.com']);
    $this->voter = StubUser::create(['email' => 'voter@example.com']);
    $this->board = Board::factory()->create();
    $this->thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
    Post::factory()->root()->create(['thread_id' => $this->thread->id, 'user_id' => $this->author->id]);
});

it('lists a thread replies (excluding the root) with pagination', function () {
    Post::factory()->count(3)->create(['thread_id' => $this->thread->id, 'user_id' => $this->replier->id]);

    $response = $this->getJson('/api/forum/threads/'.$this->thread->id.'/posts?per_page=2')->assertOk();

    expect($response->json('meta.pagination.total'))->toBe(3)
        ->and($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('is_root')->every(fn ($v) => $v === false))->toBeTrue();
});

it('creates a reply for an authenticated user', function () {
    $this->actingAs($this->replier)
        ->postJson('/api/forum/threads/'.$this->thread->id.'/posts', ['body' => 'My reply'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'My reply')
        ->assertJsonPath('data.is_root', false);

    expect($this->thread->fresh()->reply_count)->toBe(1);
});

it('forbids replying to a locked thread', function () {
    $locked = Thread::factory()->locked()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);

    $this->actingAs($this->replier)
        ->postJson('/api/forum/threads/'.$locked->id.'/posts', ['body' => 'nope'])
        ->assertForbidden();
});

it('lets the author edit their reply within the window but forbids a stranger', function () {
    $reply = $this->thread->reply($this->replier, 'original');

    $this->actingAs($this->replier)
        ->patchJson('/api/forum/posts/'.$reply->id, ['body' => 'edited'])
        ->assertOk()
        ->assertJsonPath('data.body', 'edited');

    $this->actingAs($this->voter)
        ->patchJson('/api/forum/posts/'.$reply->id, ['body' => 'hijack'])
        ->assertForbidden();
});

it('lets the author delete their reply', function () {
    $reply = $this->thread->reply($this->replier, 'to delete');

    $this->actingAs($this->replier)
        ->deleteJson('/api/forum/posts/'.$reply->id)
        ->assertNoContent();

    expect($reply->fresh()->trashed())->toBeTrue();
});

it('votes on a post and reflects the score, then unvotes', function () {
    $reply = $this->thread->reply($this->replier, 'vote me');

    $this->actingAs($this->voter)
        ->postJson('/api/forum/posts/'.$reply->id.'/vote', ['value' => 'up'])
        ->assertOk()
        ->assertJsonPath('data.score', 1);

    expect($reply->fresh()->score)->toBe(1);

    $this->actingAs($this->voter)
        ->deleteJson('/api/forum/posts/'.$reply->id.'/vote')
        ->assertOk()
        ->assertJsonPath('data.score', 0);

    expect($reply->fresh()->score)->toBe(0);
});

it('rejects a self-vote by default via the policy', function () {
    $reply = $this->thread->reply($this->replier, 'my own post');

    $this->actingAs($this->replier)
        ->postJson('/api/forum/posts/'.$reply->id.'/vote', ['value' => 'up'])
        ->assertForbidden();
});

it('validates the vote value', function () {
    $reply = $this->thread->reply($this->replier, 'vote me');

    $this->actingAs($this->voter)
        ->postJson('/api/forum/posts/'.$reply->id.'/vote', ['value' => 'sideways'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

it('blocks guests from post writes with 401', function () {
    $reply = $this->thread->reply($this->replier, 'x');

    $this->postJson('/api/forum/threads/'.$this->thread->id.'/posts', ['body' => 'x'])->assertUnauthorized();
    $this->patchJson('/api/forum/posts/'.$reply->id, ['body' => 'x'])->assertUnauthorized();
    $this->deleteJson('/api/forum/posts/'.$reply->id)->assertUnauthorized();
    $this->postJson('/api/forum/posts/'.$reply->id.'/vote', ['value' => 'up'])->assertUnauthorized();
    $this->deleteJson('/api/forum/posts/'.$reply->id.'/vote')->assertUnauthorized();
});
