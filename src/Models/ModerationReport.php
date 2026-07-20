<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Carbon\CarbonInterface;
use Database\Factories\Kurt\Modules\Forum\ModerationReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Events\ModerationReportDismissed;
use Kurt\Modules\Forum\Events\ModerationReportResolved;

/**
 * @property int $id
 * @property int $post_id
 * @property int $reporter_id
 * @property string $reason
 * @property string|null $notes
 * @property ReportState $state
 * @property CarbonInterface|null $handled_at
 * @property int|null $handled_by
 * @property Post $post
 */
class ModerationReport extends Model
{
    /** @use HasFactory<ModerationReportFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'forum_moderation_reports';

    /** @var list<string> */
    protected $fillable = [
        'post_id', 'reporter_id', 'reason', 'notes',
        'state', 'handled_at', 'handled_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'state' => ReportState::class,
        'handled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->userBelongsTo('reporter_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->userBelongsTo('handled_by');
    }

    public function resolve(Model $handler): void
    {
        // Only a pending report can be handled; ignore an already-handled one
        // so handled_at/handled_by are never overwritten and the event is not
        // re-dispatched.
        if ($this->state !== ReportState::Pending) {
            return;
        }

        $this->forceFill([
            'state' => ReportState::Resolved->value,
            'handled_at' => now(),
            'handled_by' => $handler->getKey(),
        ])->save();

        ModerationReportResolved::dispatch($this);
    }

    public function dismiss(Model $handler): void
    {
        if ($this->state !== ReportState::Pending) {
            return;
        }

        $this->forceFill([
            'state' => ReportState::Dismissed->value,
            'handled_at' => now(),
            'handled_by' => $handler->getKey(),
        ])->save();

        ModerationReportDismissed::dispatch($this);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopePending(Builder $q): Builder
    {
        return $q->where('state', ReportState::Pending->value);
    }

    protected static function newFactory(): ModerationReportFactory
    {
        return ModerationReportFactory::new();
    }
}
