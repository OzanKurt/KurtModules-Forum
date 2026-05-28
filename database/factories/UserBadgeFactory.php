<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Models\UserBadge;

/**
 * @extends Factory<UserBadge>
 */
class UserBadgeFactory extends Factory
{
    /** @var class-string<UserBadge> */
    protected $model = UserBadge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'awarded_at' => now(),
            'context' => null,
        ];
    }
}
