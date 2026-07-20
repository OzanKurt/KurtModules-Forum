<?php

declare(strict_types=1);

use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->stranger = StubUser::create(['email' => 'stranger@example.com']);
    $this->board = Board::factory()->create();
    $this->thread = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
    $this->answer = Post::factory()->create(['thread_id' => $this->thread->id, 'user_id' => $this->stranger->id]);
});

it('lets the thread author mark a solution', function () {
    $this->actingAs($this->author)
        ->postJson('/api/forum/threads/'.$this->thread->id.'/solution', ['post_id' => $this->answer->id])
        ->assertOk()
        ->assertJsonPath('data.solution_post_id', $this->answer->id);

    expect($this->thread->fresh()->solution_post_id)->toBe($this->answer->id);
});

it('forbids a stranger from marking a solution', function () {
    $this->actingAs($this->stranger)
        ->postJson('/api/forum/threads/'.$this->thread->id.'/solution', ['post_id' => $this->answer->id])
        ->assertForbidden();

    expect($this->thread->fresh()->solution_post_id)->toBeNull();
});

it('lets the author unmark a solution', function () {
    $this->thread->markSolution($this->answer);

    $this->actingAs($this->author)
        ->deleteJson('/api/forum/threads/'.$this->thread->id.'/solution')
        ->assertOk()
        ->assertJsonPath('data.solution_post_id', null);

    expect($this->thread->fresh()->solution_post_id)->toBeNull();
});

it('forbids a stranger from unmarking a solution', function () {
    $this->thread->markSolution($this->answer);

    $this->actingAs($this->stranger)
        ->deleteJson('/api/forum/threads/'.$this->thread->id.'/solution')
        ->assertForbidden();

    expect($this->thread->fresh()->solution_post_id)->toBe($this->answer->id);
});

it('rejects a solution post that belongs to another thread', function () {
    $other = Thread::factory()->create(['board_id' => $this->board->id, 'user_id' => $this->author->id]);
    $foreign = Post::factory()->create(['thread_id' => $other->id, 'user_id' => $this->stranger->id]);

    $this->actingAs($this->author)
        ->postJson('/api/forum/threads/'.$this->thread->id.'/solution', ['post_id' => $foreign->id])
        ->assertStatus(422);

    expect($this->thread->fresh()->solution_post_id)->toBeNull();
});

it('blocks guests from solution endpoints with 401', function () {
    $this->postJson('/api/forum/threads/'.$this->thread->id.'/solution', ['post_id' => $this->answer->id])
        ->assertUnauthorized();
    $this->deleteJson('/api/forum/threads/'.$this->thread->id.'/solution')->assertUnauthorized();
});
