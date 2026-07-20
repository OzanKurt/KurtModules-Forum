<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Carbon\CarbonInterface;
use Database\Factories\Kurt\Modules\Forum\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\PostReported;
use Kurt\Modules\Forum\Events\PostScoreChanged;
use Kurt\Modules\Forum\Events\VoteCast;
use Kurt\Modules\Forum\Events\VoteRevoked;
use Kurt\Modules\Interactions\Engagement\Concerns\Voteable;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $parent_id
 * @property int $user_id
 * @property string $body
 * @property bool $is_root
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $edited_at
 * @property int|null $edited_by
 * @property int $score
 * @property int $reported_count
 * @property Thread $thread
 * @property Post|null $parent
 * @property Collection<int, Post> $replies
 * @property Collection<int, Interaction> $votes
 */
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use ResolvesUser;
    use SoftDeletes;
    use Voteable;

    protected $table = 'forum_posts';

    /** @var list<string> */
    protected $fillable = [
        'thread_id', 'parent_id', 'user_id', 'body',
        'is_root', 'edited_at', 'edited_by',
        'score', 'reported_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_root' => 'bool',
        'edited_at' => 'datetime',
        'score' => 'integer',
        'reported_count' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk((string) config('forum.media.disk', 'public'));
    }

    /**
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * Vote rows for this post — stored polymorphically by the Interactions module.
     *
     * @return MorphMany<Interaction, $this>
     */
    public function votes(): MorphMany
    {
        return $this->receivedInteractions()->where('type', InteractionType::Vote->value);
    }

    /**
     * Cast a vote. Idempotent: casting the same value twice toggles the vote off.
     * Returns the resulting Interaction, or null if toggled off or rejected.
     */
    public function vote(Model $user, VoteValue $value): ?Interaction
    {
        if (! (bool) config('forum.allow_self_vote') && $this->user_id === $user->getKey()) {
            return null;
        }

        return DB::transaction(function () use ($user, $value): ?Interaction {
            $manager = app(InteractionManager::class);

            /** @var Interaction|null $existing */
            $existing = $this->votes()->where('user_id', $user->getKey())->first();

            if ($existing !== null && (int) $existing->value === $value->value) {
                $manager->remove($user, $this, InteractionType::Vote);
                $this->refreshScore();
                VoteRevoked::dispatch($this, $user);

                return null;
            }

            $vote = $manager->add($user, $this, InteractionType::Vote, $value->value);

            $this->refreshScore();
            VoteCast::dispatch($this, $value);

            return $vote;
        });
    }

    /**
     * Remove this user's vote, if any. Idempotent: returns false when the user
     * had not voted. Dispatches `VoteRevoked` when a vote is actually removed.
     */
    public function unvote(Model $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            /** @var Interaction|null $existing */
            $existing = $this->votes()->where('user_id', $user->getKey())->first();

            if ($existing === null) {
                return false;
            }

            app(InteractionManager::class)->remove($user, $this, InteractionType::Vote);
            $this->refreshScore();
            VoteRevoked::dispatch($this, $user);

            return true;
        });
    }

    /**
     * Submit a moderation report against this post.
     *
     * Idempotent per reporter: a reporter that has already reported this post
     * gets their existing report back without bumping reported_count or
     * re-dispatching. Self-reports are rejected as a no-op (returns null).
     */
    public function report(Model $reporter, string $reason, ?string $notes = null): ?ModerationReport
    {
        if ($this->user_id === $reporter->getKey()) {
            return null;
        }

        return DB::transaction(function () use ($reporter, $reason, $notes): ModerationReport {
            /** @var ModerationReport $report */
            $report = ModerationReport::query()->firstOrCreate(
                [
                    'post_id' => $this->id,
                    'reporter_id' => $reporter->getKey(),
                ],
                [
                    'reason' => $reason,
                    'notes' => $notes,
                    'state' => ReportState::Pending->value,
                ],
            );

            if ($report->wasRecentlyCreated) {
                $this->forceFill(['reported_count' => $this->reported_count + 1])->save();

                PostReported::dispatch($report);
            }

            return $report;
        });
    }

    /**
     * Whether this post is the accepted answer of its thread.
     */
    public function isSolution(): bool
    {
        return $this->thread->solution_post_id === $this->id;
    }

    public function refreshScore(): int
    {
        $score = (int) $this->votes()->sum('value');
        $this->forceFill(['score' => $score])->save();

        if ($this->is_root) {
            // Keep Thread.score in sync with root post score.
            Thread::query()->whereKey($this->thread_id)->update(['score' => $score]);
        }

        PostScoreChanged::dispatch($this);

        return $score;
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeReplies(Builder $q): Builder
    {
        return $q->where('is_root', false);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeRoots(Builder $q): Builder
    {
        return $q->where('is_root', true);
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
