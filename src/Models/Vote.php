<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Database\Factories\Kurt\Modules\Forum\VoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property int $value
 * @property Post $post
 */
class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory;

    protected $table = 'forum_votes';

    /** @var list<string> */
    protected $fillable = ['post_id', 'user_id', 'value'];

    /** @var array<string, string> */
    protected $casts = ['value' => 'integer'];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    protected static function newFactory(): VoteFactory
    {
        return VoteFactory::new();
    }
}
