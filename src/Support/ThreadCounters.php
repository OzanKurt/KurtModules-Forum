<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Support;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

/**
 * Pure recompute helpers for denormalised counters. Used by `forum:recount`
 * and from tests that need to assert "would the live increments have produced
 * the same values?".
 */
final class ThreadCounters
{
    /**
     * Recompute reply_count, last_post_id, last_post_at, score on the thread.
     */
    public static function recount(Thread $thread): void
    {
        $replyCount = (int) DB::table('forum_posts')
            ->where('thread_id', $thread->id)
            ->where('is_root', false)
            ->whereNull('deleted_at')
            ->count();

        $rootScore = (int) DB::table('interactions_interactions')
            ->join('forum_posts', 'forum_posts.id', '=', 'interactions_interactions.subject_id')
            ->where('interactions_interactions.subject_type', Post::class)
            ->where('interactions_interactions.type', 'vote')
            ->where('forum_posts.thread_id', $thread->id)
            ->where('forum_posts.is_root', true)
            ->whereNull('forum_posts.deleted_at')
            ->sum('interactions_interactions.value');

        $latest = DB::table('forum_posts')
            ->where('thread_id', $thread->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->select(['id', 'created_at'])
            ->first();

        $thread->forceFill([
            'reply_count' => $replyCount,
            'score' => $rootScore,
            'last_post_id' => $latest !== null ? (int) $latest->id : null,
            'last_post_at' => $latest !== null ? $latest->created_at : null,
        ])->save();
    }

    /**
     * Recompute thread_count + post_count + last_post_at on the board.
     */
    public static function recountBoard(Board $board): void
    {
        $threadCount = (int) DB::table('forum_threads')
            ->where('board_id', $board->id)
            ->whereNull('deleted_at')
            ->count();

        $postCount = (int) DB::table('forum_posts')
            ->join('forum_threads', 'forum_threads.id', '=', 'forum_posts.thread_id')
            ->where('forum_threads.board_id', $board->id)
            ->whereNull('forum_posts.deleted_at')
            ->whereNull('forum_threads.deleted_at')
            ->count();

        /** @var string|null $lastPostAt */
        $lastPostAt = DB::table('forum_posts')
            ->join('forum_threads', 'forum_threads.id', '=', 'forum_posts.thread_id')
            ->where('forum_threads.board_id', $board->id)
            ->whereNull('forum_posts.deleted_at')
            ->whereNull('forum_threads.deleted_at')
            ->max('forum_posts.created_at');

        $board->forceFill([
            'thread_count' => $threadCount,
            'post_count' => $postCount,
            'last_post_at' => $lastPostAt,
        ])->save();
    }

    /**
     * Recompute score on the post from sum(votes.value).
     */
    public static function recountPost(Post $post): void
    {
        $score = (int) DB::table('interactions_interactions')
            ->where('subject_type', Post::class)
            ->where('subject_id', $post->id)
            ->where('type', 'vote')
            ->sum('value');
        $post->forceFill(['score' => $score])->save();
    }
}
