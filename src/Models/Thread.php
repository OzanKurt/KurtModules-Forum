<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Carbon\CarbonInterface;
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Forum\ThreadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Forum\Events\SolutionMarked;
use Kurt\Modules\Forum\Events\SolutionUnmarked;
use Kurt\Modules\Forum\Events\SubscriptionCreated;
use Kurt\Modules\Forum\Events\SubscriptionRemoved;
use Kurt\Modules\Forum\Events\ThreadReplied;
use Kurt\Modules\Forum\Exceptions\SolutionPostMismatchException;
use Kurt\Modules\Forum\Exceptions\ThreadLockedException;

/**
 * @property int $id
 * @property string $slug
 * @property int $board_id
 * @property int $user_id
 * @property string $title
 * @property bool $is_pinned
 * @property bool $is_locked
 * @property bool $is_hidden
 * @property int $views
 * @property int $score
 * @property int $reply_count
 * @property int|null $last_post_id
 * @property CarbonInterface|null $last_post_at
 * @property int|null $solution_post_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property Board $board
 * @property Collection<int, Post> $posts
 * @property Post|null $rootPost
 * @property Post|null $solutionPost
 * @property Collection<int, Subscription> $subscriptions
 */
class Thread extends Model
{
    /** @use HasFactory<ThreadFactory> */
    use HasFactory;

    use ResolvesUser;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'forum_threads';

    /** @var list<string> */
    protected $fillable = [
        'slug', 'board_id', 'user_id', 'title',
        'is_pinned', 'is_locked', 'is_hidden',
        'views', 'score', 'reply_count',
        'last_post_id', 'last_post_at',
        'solution_post_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_pinned' => 'bool',
        'is_locked' => 'bool',
        'is_hidden' => 'bool',
        'views' => 'integer',
        'score' => 'integer',
        'reply_count' => 'integer',
        'last_post_at' => 'datetime',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title', 'onUpdate' => false]];
    }

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasOne<Post, $this>
     */
    public function rootPost(): HasOne
    {
        return $this->hasOne(Post::class)->where('is_root', true);
    }

    /**
     * The post accepted as this thread's answer, if any.
     *
     * @return BelongsTo<Post, $this>
     */
    public function solutionPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'solution_post_id');
    }

    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * Reply to this thread. Atomically increments reply_count + board.post_count and
     * dispatches `ThreadReplied`. PostObserver dispatches `PostCreated`.
     *
     * @throws ThreadLockedException when the thread is locked to further replies.
     */
    public function reply(Model $user, string $body, ?Post $parent = null): Post
    {
        // Locking is a moderation state that closes the thread to new replies.
        // Reject the write explicitly instead of silently creating a post.
        if ($this->is_locked) {
            throw ThreadLockedException::for($this);
        }

        return DB::transaction(function () use ($user, $body, $parent): Post {
            /** @var Post $post */
            $post = $this->posts()->create([
                'parent_id' => $parent?->id,
                'user_id' => $user->getKey(),
                'body' => $body,
                'is_root' => false,
            ]);

            $now = now();

            $this->forceFill([
                'reply_count' => $this->reply_count + 1,
                'last_post_id' => $post->id,
                'last_post_at' => $now,
            ])->save();

            // Atomic increment + last_post_at on the board.
            Board::query()
                ->whereKey($this->board_id)
                ->update([
                    'post_count' => DB::raw('post_count + 1'),
                    'last_post_at' => $now,
                ]);

            /** @var Thread $fresh */
            $fresh = $this->fresh();

            ThreadReplied::dispatch($fresh, $post);

            return $post;
        });
    }

    public function subscribe(Model $user): Subscription
    {
        $created = false;

        /** @var Subscription $subscription */
        $subscription = Subscription::query()->firstOrCreate(
            [
                'user_id' => $user->getKey(),
                'subscribable_type' => self::class,
                'subscribable_id' => $this->id,
            ],
            [],
        );

        if ($subscription->wasRecentlyCreated) {
            $created = true;
        }

        if ($created) {
            SubscriptionCreated::dispatch($subscription);
        }

        return $subscription;
    }

    public function unsubscribe(Model $user): bool
    {
        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->where('user_id', $user->getKey())
            ->where('subscribable_type', self::class)
            ->where('subscribable_id', $this->id)
            ->first();

        if ($subscription === null) {
            return false;
        }

        $subscription->delete();
        SubscriptionRemoved::dispatch($subscription);

        return true;
    }

    /**
     * Mark one of this thread's posts as the accepted answer. Idempotent when
     * the post is already the solution. Dispatches `SolutionMarked`.
     *
     * @throws SolutionPostMismatchException when the post belongs to another thread.
     */
    public function markSolution(Post $post): void
    {
        if ($post->thread_id !== $this->id) {
            throw SolutionPostMismatchException::for($this, $post);
        }

        if ($this->solution_post_id === $post->id) {
            return;
        }

        $this->forceFill(['solution_post_id' => $post->id])->save();

        SolutionMarked::dispatch($this, $post);
    }

    /**
     * Clear this thread's accepted answer. No-op when the thread is unsolved.
     * Dispatches `SolutionUnmarked` with the previously-marked post.
     */
    public function unmarkSolution(): void
    {
        if ($this->solution_post_id === null) {
            return;
        }

        // Resolve the previous solution before clearing the pointer so the event
        // can carry it. It may be null if that post row has since been removed.
        $previous = $this->solutionPost;

        $this->forceFill(['solution_post_id' => null])->save();

        SolutionUnmarked::dispatch($this, $previous);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeSolved(Builder $q): Builder
    {
        return $q->whereNotNull('solution_post_id');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeUnsolved(Builder $q): Builder
    {
        return $q->whereNull('solution_post_id');
    }

    /**
     * Search threads by title and their posts' bodies. Returns distinct threads
     * (one row per thread via a correlated EXISTS, no join fan-out), ranked with
     * title matches first, then most-recently-active. Uses MySQL/MariaDB FULLTEXT
     * (MATCH...AGAINST) when the connection supports it, else a portable LIKE.
     *
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            // An empty term matches nothing rather than every thread.
            return $q->whereRaw('1 = 0');
        }

        $threads = $this->getTable();
        $posts = (new Post)->getTable();
        $driver = $q->getModel()->getConnection()->getDriverName();
        $fullText = in_array($driver, ['mysql', 'mariadb'], true);

        $q->where(function (Builder $sub) use ($term, $threads, $posts, $fullText): void {
            if ($fullText) {
                $sub->whereFullText($threads.'.title', $term)
                    ->orWhereExists(function ($query) use ($term, $threads, $posts): void {
                        $query->selectRaw('1')
                            ->from($posts)
                            ->whereColumn($posts.'.thread_id', $threads.'.id')
                            ->whereFullText($posts.'.body', $term);
                    });

                return;
            }

            $like = '%'.$term.'%';

            $sub->where($threads.'.title', 'like', $like)
                ->orWhereExists(function ($query) use ($like, $threads, $posts): void {
                    $query->selectRaw('1')
                        ->from($posts)
                        ->whereColumn($posts.'.thread_id', $threads.'.id')
                        ->where($posts.'.body', 'like', $like);
                });
        });

        // Rank title hits above body-only hits, then by recent activity. The LIKE
        // in the CASE is portable and cheap enough to drive ordering on any driver.
        return $q
            ->orderByRaw('(case when '.$threads.'.title like ? then 1 else 0 end) desc', ['%'.$term.'%'])
            ->orderByDesc($threads.'.last_post_at');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeForBoard(Builder $q, Board|int $board): Builder
    {
        return $q->where('board_id', $board instanceof Board ? $board->id : $board);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopePinnedFirst(Builder $q): Builder
    {
        return $q->orderByDesc('is_pinned')->orderByDesc('last_post_at');
    }

    protected static function newFactory(): ThreadFactory
    {
        return ThreadFactory::new();
    }
}
