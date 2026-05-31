<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'owner@example.com']);
});

it('rebuilds Board.thread_count + Board.post_count after corruption', function () {
    /** @var Board $board */
    $board = Board::factory()->create();

    /** @var Thread $thread1 */
    $thread1 = Thread::factory()->create(['board_id' => $board->id, 'user_id' => $this->user->id]);
    /** @var Thread $thread2 */
    $thread2 = Thread::factory()->create(['board_id' => $board->id, 'user_id' => $this->user->id]);

    Post::create(['thread_id' => $thread1->id, 'user_id' => $this->user->id, 'body' => 'op', 'is_root' => true]);
    $thread1->reply($this->user, 'reply 1');
    $thread1->reply($this->user, 'reply 2');
    Post::create(['thread_id' => $thread2->id, 'user_id' => $this->user->id, 'body' => 'op2', 'is_root' => true]);

    // Corrupt counters.
    DB::table('forum_boards')->where('id', $board->id)->update(['thread_count' => 999, 'post_count' => 999]);
    DB::table('forum_threads')->where('id', $thread1->id)->update(['reply_count' => 999]);

    expect(Board::find($board->id)->thread_count)->toBe(999);
    expect(Thread::find($thread1->id)->reply_count)->toBe(999);

    $this->artisan('forum:recount')->assertSuccessful();

    $boardFresh = Board::find($board->id);
    expect($boardFresh->thread_count)->toBe(2);
    expect($boardFresh->post_count)->toBe(4);

    expect(Thread::find($thread1->id)->reply_count)->toBe(2);
});

it('rebuilds Post.score from raw vote rows in Interactions', function () {
    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create(['board_id' => $board->id, 'user_id' => $this->user->id]);
    /** @var Post $post */
    $post = Post::create(['thread_id' => $thread->id, 'user_id' => $this->user->id, 'body' => 'op', 'is_root' => true]);

    DB::table('interactions_interactions')->insert([
        ['user_id' => StubUser::create(['email' => 'v1@x.com'])->id, 'subject_type' => Post::class, 'subject_id' => $post->id, 'type' => 'vote', 'value' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => StubUser::create(['email' => 'v2@x.com'])->id, 'subject_type' => Post::class, 'subject_id' => $post->id, 'type' => 'vote', 'value' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => StubUser::create(['email' => 'v3@x.com'])->id, 'subject_type' => Post::class, 'subject_id' => $post->id, 'type' => 'vote', 'value' => -1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Corrupt score.
    DB::table('forum_posts')->where('id', $post->id)->update(['score' => 999]);

    $this->artisan('forum:recount')->assertSuccessful();

    expect(Post::find($post->id)->score)->toBe(1);
});

it('does not change correct counters', function () {
    /** @var Board $board */
    $board = Board::factory()->create();
    /** @var Thread $thread */
    $thread = Thread::factory()->create(['board_id' => $board->id, 'user_id' => $this->user->id]);
    Post::create(['thread_id' => $thread->id, 'user_id' => $this->user->id, 'body' => 'op', 'is_root' => true]);
    $thread->reply($this->user, 'r1');

    $boardBefore = Board::find($board->id);
    $threadBefore = Thread::find($thread->id);

    $this->artisan('forum:recount')->assertSuccessful();

    $boardAfter = Board::find($board->id);
    $threadAfter = Thread::find($thread->id);

    expect($boardAfter->thread_count)->toBe($boardBefore->thread_count);
    expect($boardAfter->post_count)->toBe($boardBefore->post_count);
    expect($threadAfter->reply_count)->toBe($threadBefore->reply_count);
});
