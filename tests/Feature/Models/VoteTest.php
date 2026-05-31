<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\VoteCast;
use Kurt\Modules\Forum\Events\VoteRevoked;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->voter = StubUser::create(['email' => 'voter@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $this->author->id,
    ]);

    /** @var Post $post */
    $post = Post::create([
        'thread_id' => $thread->id,
        'user_id' => $this->author->id,
        'body' => 'Body',
        'is_root' => true,
    ]);
    $this->post = $post;
});

function forumVoteCount(Post $post): int
{
    return Interaction::query()
        ->where('subject_type', Post::class)
        ->where('subject_id', $post->id)
        ->where('type', 'vote')
        ->count();
}

it('creates an up vote (stored in Interactions) and bumps the post score', function () {
    Event::fake([VoteCast::class]);

    $vote = $this->post->vote($this->voter, VoteValue::Up);

    expect($vote)->toBeInstanceOf(Interaction::class);
    expect($this->post->fresh()->score)->toBe(1);
    expect(forumVoteCount($this->post))->toBe(1);

    Event::assertDispatched(VoteCast::class);
});

it('toggles off an existing vote when the same value is cast again', function () {
    Event::fake([VoteCast::class, VoteRevoked::class]);

    $this->post->vote($this->voter, VoteValue::Up);
    expect($this->post->fresh()->score)->toBe(1);

    $result = $this->post->vote($this->voter, VoteValue::Up);

    expect($result)->toBeNull();
    expect($this->post->fresh()->score)->toBe(0);
    expect(forumVoteCount($this->post))->toBe(0);

    Event::assertDispatched(VoteRevoked::class);
});

it('updates an existing vote when the opposite value is cast', function () {
    $this->post->vote($this->voter, VoteValue::Up);
    expect($this->post->fresh()->score)->toBe(1);

    $this->post->vote($this->voter, VoteValue::Down);

    expect($this->post->fresh()->score)->toBe(-1);
    expect(forumVoteCount($this->post))->toBe(1);
});

it('rejects a self-vote when allow_self_vote is disabled', function () {
    config()->set('forum.allow_self_vote', false);

    $result = $this->post->vote($this->author, VoteValue::Up);

    expect($result)->toBeNull();
    expect(forumVoteCount($this->post))->toBe(0);
    expect($this->post->fresh()->score)->toBe(0);
});

it('allows a self-vote when allow_self_vote is enabled', function () {
    config()->set('forum.allow_self_vote', true);

    $result = $this->post->vote($this->author, VoteValue::Up);

    expect($result)->toBeInstanceOf(Interaction::class);
    expect($this->post->fresh()->score)->toBe(1);
});

it('keeps Thread.score in sync with the root post score', function () {
    $voter2 = StubUser::create(['email' => 'voter2@example.com']);

    $this->post->vote($this->voter, VoteValue::Up);
    $this->post->vote($voter2, VoteValue::Up);

    $thread = $this->post->thread()->first();
    expect($thread->score)->toBe(2);

    $this->post->vote($this->voter, VoteValue::Up); // toggle off

    expect($this->post->thread()->first()->score)->toBe(1);
});
