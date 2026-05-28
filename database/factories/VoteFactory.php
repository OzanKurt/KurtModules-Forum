<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Enums\VoteValue;
use Kurt\Modules\Forum\Models\Vote;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /** @var class-string<Vote> */
    protected $model = Vote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => VoteValue::Up->value,
        ];
    }

    public function up(): static
    {
        return $this->state(fn () => ['value' => VoteValue::Up->value]);
    }

    public function down(): static
    {
        return $this->state(fn () => ['value' => VoteValue::Down->value]);
    }
}
