<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Carbon\CarbonInterface;
use Database\Factories\Kurt\Modules\Forum\UserBadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $user_id
 * @property int $badge_id
 * @property CarbonInterface $awarded_at
 * @property array<string, mixed>|null $context
 * @property Badge $badge
 */
class UserBadge extends Model
{
    /** @use HasFactory<UserBadgeFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'forum_user_badges';

    /** @var list<string> */
    protected $fillable = ['user_id', 'badge_id', 'awarded_at', 'context'];

    /** @var array<string, string> */
    protected $casts = [
        'awarded_at' => 'datetime',
        'context' => 'array',
    ];

    /**
     * @return BelongsTo<Badge, $this>
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): UserBadgeFactory
    {
        return UserBadgeFactory::new();
    }
}
