<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Forum\Events\SolutionMarked;
use Kurt\Modules\Forum\Events\SolutionUnmarked;
use Kurt\Modules\Forum\Exceptions\SolutionPostMismatchException;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->replier = StubUser::create(['email' => 'replier@example.com']);
    $this->moderator = StubUser::create(['email' => 'mod@example.com']);

    /** @var Board $board */
    $board = Board::factory()->create();
    $this->board = $board;

    /** @var Thread $thread */
    $thread = Thread::factory()->create([
        'board_id' => $board->id,
        'user_id' => $this->author->id,
    ]);
    $this->thread = $thread;

    /** @var Post $answer */
    $answer = Post::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $this->replier->id,
    ]);
    $this->answer = $answer;
});

it('marks a post as the thread solution', function () {
    $this->thread->markSolution($this->answer);

    expect($this->thread->fresh()->solution_post_id)->toBe($this->answer->id);
    expect($this->answer->fresh()->isSolution())->toBeTrue();
});

it('reports solved/unsolved state via scopes', function () {
    expect(Thread::query()->solved()->count())->toBe(0);
    expect(Thread::query()->unsolved()->count())->toBe(1);

    $this->thread->markSolution($this->answer);

    expect(Thread::query()->solved()->pluck('id')->all())->toBe([$this->thread->id]);
    expect(Thread::query()->unsolved()->count())->toBe(0);
});

it('unmarks the solution and returns to unsolved', function () {
    $this->thread->markSolution($this->answer);
    $this->thread->unmarkSolution();

    expect($this->thread->fresh()->solution_post_id)->toBeNull();
    expect($this->answer->fresh()->isSolution())->toBeFalse();
    expect(Thread::query()->unsolved()->count())->toBe(1);
});

it('dispatches SolutionMarked when a solution is set', function () {
    Event::fake([SolutionMarked::class]);

    $this->thread->markSolution($this->answer);

    Event::assertDispatched(SolutionMarked::class, function (SolutionMarked $event): bool {
        return $event->thread->id === $this->thread->id
            && $event->post->id === $this->answer->id;
    });
});

it('dispatches SolutionUnmarked with the previous post when cleared', function () {
    $this->thread->markSolution($this->answer);

    Event::fake([SolutionUnmarked::class]);

    $this->thread->unmarkSolution();

    Event::assertDispatched(SolutionUnmarked::class, function (SolutionUnmarked $event): bool {
        return $event->thread->id === $this->thread->id
            && $event->post?->id === $this->answer->id;
    });
});

it('is idempotent when marking the same post twice', function () {
    $this->thread->markSolution($this->answer);

    Event::fake([SolutionMarked::class]);
    $this->thread->markSolution($this->answer);

    Event::assertNotDispatched(SolutionMarked::class);
    expect($this->thread->fresh()->solution_post_id)->toBe($this->answer->id);
});

it('is a no-op when unmarking an already-unsolved thread', function () {
    Event::fake([SolutionUnmarked::class]);

    $this->thread->unmarkSolution();

    Event::assertNotDispatched(SolutionUnmarked::class);
    expect($this->thread->fresh()->solution_post_id)->toBeNull();
});

it('rejects a post that belongs to another thread', function () {
    /** @var Thread $other */
    $other = Thread::factory()->create([
        'board_id' => $this->board->id,
        'user_id' => $this->author->id,
    ]);

    /** @var Post $foreign */
    $foreign = Post::factory()->create([
        'thread_id' => $other->id,
        'user_id' => $this->replier->id,
    ]);

    expect(fn () => $this->thread->markSolution($foreign))
        ->toThrow(SolutionPostMismatchException::class);

    expect($this->thread->fresh()->solution_post_id)->toBeNull();
});

it('authorizes the thread author to mark a solution', function () {
    expect(Gate::forUser($this->author)->allows('markSolution', $this->thread))->toBeTrue();
    expect(Gate::forUser($this->author)->allows('unmarkSolution', $this->thread))->toBeTrue();
});

it('denies a non-author non-moderator from marking a solution', function () {
    expect(Gate::forUser($this->replier)->allows('markSolution', $this->thread))->toBeFalse();
});

it('authorizes a moderator via the canModerateForum gate', function () {
    Gate::define('canModerateForum', fn ($user): bool => (int) $user->getAuthIdentifier() === $this->moderator->id);

    expect(Gate::forUser($this->moderator)->allows('markSolution', $this->thread))->toBeTrue();
    expect(Gate::forUser($this->replier)->allows('markSolution', $this->thread))->toBeFalse();
});
