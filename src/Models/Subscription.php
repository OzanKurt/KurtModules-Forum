<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Database\Factories\Kurt\Modules\Forum\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $subscribable_type
 * @property int $subscribable_id
 * @property Model $subscribable
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $table = 'forum_subscriptions';

    /** @var list<string> */
    protected $fillable = ['user_id', 'subscribable_type', 'subscribable_id'];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }
}
