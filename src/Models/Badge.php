<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Models;

use Database\Factories\Kurt\Modules\Forum\BadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kurt\Modules\Forum\Enums\BadgeRarity;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string|null $icon
 * @property BadgeRarity $rarity
 * @property bool $is_active
 */
class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

    use HasTranslations;

    protected $table = 'forum_badges';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = ['slug', 'name', 'description', 'icon', 'rarity', 'is_active'];

    /** @var array<string, string> */
    protected $casts = [
        'rarity' => BadgeRarity::class,
        'is_active' => 'bool',
    ];

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }
}
