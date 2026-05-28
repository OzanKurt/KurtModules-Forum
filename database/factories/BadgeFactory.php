<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Enums\BadgeRarity;
use Kurt\Modules\Forum\Models\Badge;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    /** @var class-string<Badge> */
    protected $model = Badge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['en' => $name],
            'description' => ['en' => $this->faker->sentence()],
            'icon' => null,
            'rarity' => BadgeRarity::Common,
            'is_active' => true,
        ];
    }
}
