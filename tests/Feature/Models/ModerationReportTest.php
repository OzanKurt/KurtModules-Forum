<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Events\ModerationReportDismissed;
use Kurt\Modules\Forum\Events\ModerationReportResolved;
use Kurt\Modules\Forum\Events\PostReported;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\ModerationReport;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->author = StubUser::create(['email' => 'author@example.com']);
    $this->reporter = StubUser::create(['email' => 'reporter@example.com']);
    $this->moderator = StubUser::create(['email' => 'mod@example.com']);

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
        'body' => 'spam body',
        'is_root' => true,
    ]);
    $this->post = $post;
});

it('creates a pending report and bumps reported_count when Post::report runs', function () {
    Event::fake([PostReported::class]);

    $report = $this->post->report($this->reporter, 'spam', 'looks like spam');

    expect($report->state)->toBe(ReportState::Pending);
    expect($report->reason)->toBe('spam');
    expect($this->post->fresh()->reported_count)->toBe(1);

    Event::assertDispatched(PostReported::class);
});

it('resolves a pending report and fires ModerationReportResolved', function () {
    Event::fake([ModerationReportResolved::class]);

    $report = $this->post->report($this->reporter, 'spam');
    expect($report->state)->toBe(ReportState::Pending);

    $report->resolve($this->moderator);

    $fresh = $report->fresh();
    expect($fresh->state)->toBe(ReportState::Resolved);
    expect($fresh->handled_at)->not->toBeNull();
    expect($fresh->handled_by)->toBe($this->moderator->id);

    Event::assertDispatched(ModerationReportResolved::class);
});

it('dismisses a pending report and fires ModerationReportDismissed', function () {
    Event::fake([ModerationReportDismissed::class]);

    $report = $this->post->report($this->reporter, 'off-topic');

    $report->dismiss($this->moderator);

    $fresh = $report->fresh();
    expect($fresh->state)->toBe(ReportState::Dismissed);
    expect($fresh->handled_at)->not->toBeNull();
    expect($fresh->handled_by)->toBe($this->moderator->id);

    Event::assertDispatched(ModerationReportDismissed::class);
});

it('scopes only pending reports via scopePending', function () {
    $other = StubUser::create(['email' => 'reporter2@example.com']);

    $r1 = $this->post->report($this->reporter, 'spam');
    $r2 = $this->post->report($other, 'other');
    $r2->resolve($this->moderator);

    expect(ModerationReport::query()->pending()->count())->toBe(1);
    expect(ModerationReport::query()->pending()->first()->id)->toBe($r1->id);
});

it('does not create a second report or bump the counter when the same reporter reports twice', function () {
    Event::fake([PostReported::class]);

    $first = $this->post->report($this->reporter, 'spam');
    $second = $this->post->report($this->reporter, 'spam again');

    expect($second->id)->toBe($first->id);
    expect(ModerationReport::query()->count())->toBe(1);
    expect($this->post->fresh()->reported_count)->toBe(1);

    Event::assertDispatchedTimes(PostReported::class, 1);
});

it('counts distinct reporters and bumps the counter once per reporter', function () {
    $other = StubUser::create(['email' => 'reporter3@example.com']);

    $this->post->report($this->reporter, 'spam');
    $this->post->report($other, 'spam');

    expect(ModerationReport::query()->count())->toBe(2);
    expect($this->post->fresh()->reported_count)->toBe(2);
});

it('rejects a self-report as a no-op without creating a report or bumping the counter', function () {
    Event::fake([PostReported::class]);

    $result = $this->post->report($this->author, 'i regret this');

    expect($result)->toBeNull();
    expect(ModerationReport::query()->count())->toBe(0);
    expect($this->post->fresh()->reported_count)->toBe(0);

    Event::assertNotDispatched(PostReported::class);
});

it('ignores resolve on an already-handled report', function () {
    $secondModerator = StubUser::create(['email' => 'mod2@example.com']);

    $report = $this->post->report($this->reporter, 'spam');
    $report->resolve($this->moderator);

    $handledAt = $report->fresh()->handled_at;

    Event::fake([ModerationReportResolved::class, ModerationReportDismissed::class]);

    $report->resolve($secondModerator);
    $report->dismiss($secondModerator);

    $fresh = $report->fresh();
    expect($fresh->state)->toBe(ReportState::Resolved);
    expect($fresh->handled_by)->toBe($this->moderator->id);
    expect($fresh->handled_at->equalTo($handledAt))->toBeTrue();

    Event::assertNotDispatched(ModerationReportResolved::class);
    Event::assertNotDispatched(ModerationReportDismissed::class);
});

it('ignores dismiss on an already-handled report', function () {
    $report = $this->post->report($this->reporter, 'spam');
    $report->dismiss($this->moderator);

    Event::fake([ModerationReportResolved::class]);

    $report->resolve($this->moderator);

    expect($report->fresh()->state)->toBe(ReportState::Dismissed);

    Event::assertNotDispatched(ModerationReportResolved::class);
});
