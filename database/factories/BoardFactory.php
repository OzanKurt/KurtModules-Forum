<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Enums\Visibility;
use Kurt\Modules\Forum\Models\Board;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /** @var class-string<Board> */
    protected $model = Board::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        $slug = str($name)->slug()->toString();

        return [
            'parent_id' => null,
            'slug' => $slug,
            'name' => ['en' => $name],
            'description' => ['en' => $this->faker->sentence()],
            'position' => 0,
            'state' => BoardState::Open,
            'visibility' => Visibility::Public,
            'thread_count' => 0,
            'post_count' => 0,
        ];
    }

    public function boardState(BoardState $state): static
    {
        return $this->state(fn () => ['state' => $state]);
    }

    public function visibility(Visibility $visibility): static
    {
        return $this->state(fn () => ['visibility' => $visibility]);
    }

    public function child(Board $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
