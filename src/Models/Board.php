<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Carbon\CarbonInterface;
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Forum\BoardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Enums\Visibility;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $position
 * @property BoardState $state
 * @property Visibility $visibility
 * @property int $thread_count
 * @property int $post_count
 * @property CarbonInterface|null $last_post_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property Board|null $parent
 * @property Collection<int, Board> $children
 * @property Collection<int, Thread> $threads
 */
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    use HasTranslations;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'forum_boards';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'parent_id', 'slug', 'name', 'description', 'position',
        'state', 'visibility', 'thread_count', 'post_count', 'last_post_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'state' => BoardState::class,
        'visibility' => Visibility::class,
        'position' => 'integer',
        'thread_count' => 'integer',
        'post_count' => 'integer',
        'last_post_at' => 'datetime',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name', 'onUpdate' => true]];
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
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('state', BoardState::Open->value);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    protected static function newFactory(): BoardFactory
    {
        return BoardFactory::new();
    }
}
