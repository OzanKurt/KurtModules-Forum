<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Models\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /** @var class-string<Post> */
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraph(),
            'is_root' => false,
            'score' => 0,
            'reported_count' => 0,
        ];
    }

    public function root(): static
    {
        return $this->state(fn () => ['is_root' => true]);
    }
}
