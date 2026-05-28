<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Thread;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    /** @var class-string<Thread> */
    protected $model = Thread::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        $slug = str($title)->slug()->toString();

        return [
            'slug' => $slug,
            'title' => $title,
            'board_id' => fn () => Board::factory()->create()->id,
            // user_id is supplied by tests.
            'is_pinned' => false,
            'is_locked' => false,
            'is_hidden' => false,
            'views' => 0,
            'score' => 0,
            'reply_count' => 0,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function locked(): static
    {
        return $this->state(fn () => ['is_locked' => true]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
