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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Events\PostReported;
use Kurt\Modules\Forum\Events\VoteCast;
use Kurt\Modules\Forum\Events\VoteRevoked;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $parent_id
 * @property int $user_id
 * @property string $body
 * @property bool $is_root
 * @property CarbonInterface|null $edited_at
 * @property int|null $edited_by
 * @property int $score
 * @property int $reported_count
 * @property Thread $thread
 * @property Post|null $parent
 * @property Collection<int, Post> $replies
 * @property Collection<int, Vote> $votes
 */
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use ResolvesUser;
    use SoftDeletes;

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
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Cast a vote. Idempotent: casting the same value twice toggles the vote off.
     * Returns the resulting Vote, or null if the vote was toggled off or rejected.
     */
    public function vote(Model $user, VoteValue $value): ?Vote
    {
        if (! (bool) config('forum.allow_self_vote') && $this->user_id === $user->getKey()) {
            return null;
        }

        return DB::transaction(function () use ($user, $value): ?Vote {
            /** @var Vote|null $existing */
            $existing = $this->votes()->where('user_id', $user->getKey())->first();

            if ($existing !== null && $existing->value === $value->value) {
                $existing->delete();
                $this->refreshScore();
                VoteRevoked::dispatch($this, $user);

                return null;
            }

            /** @var Vote $vote */
            $vote = $this->votes()->updateOrCreate(
                ['user_id' => $user->getKey()],
                ['value' => $value->value],
            );

            $this->refreshScore();
            VoteCast::dispatch($vote);

            return $vote;
        });
    }

    /**
     * Submit a moderation report against this post; bumps reported_count.
     */
    public function report(Model $reporter, string $reason, ?string $notes = null): ModerationReport
    {
        return DB::transaction(function () use ($reporter, $reason, $notes): ModerationReport {
            /** @var ModerationReport $report */
            $report = ModerationReport::query()->create([
                'post_id' => $this->id,
                'reporter_id' => $reporter->getKey(),
                'reason' => $reason,
                'notes' => $notes,
                'state' => ReportState::Pending->value,
            ]);

            $this->forceFill(['reported_count' => $this->reported_count + 1])->save();

            PostReported::dispatch($report);

            return $report;
        });
    }

    public function refreshScore(): int
    {
        $score = (int) $this->votes()->sum('value');
        $this->forceFill(['score' => $score])->save();

        if ($this->is_root) {
            // Keep Thread.score in sync with root post score.
            Thread::query()->whereKey($this->thread_id)->update(['score' => $score]);
        }

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
