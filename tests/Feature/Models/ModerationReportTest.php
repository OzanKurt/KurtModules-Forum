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
    $r1 = $this->post->report($this->reporter, 'spam');
    $r2 = $this->post->report($this->reporter, 'other');
    $r2->resolve($this->moderator);

    expect(ModerationReport::query()->pending()->count())->toBe(1);
    expect(ModerationReport::query()->pending()->first()->id)->toBe($r1->id);
});
