<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Models\ModerationReport;

/**
 * @extends Factory<ModerationReport>
 */
class ModerationReportFactory extends Factory
{
    /** @var class-string<ModerationReport> */
    protected $model = ModerationReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reason' => $this->faker->randomElement(['spam', 'harassment', 'off-topic', 'other']),
            'notes' => $this->faker->optional()->sentence(),
            'state' => ReportState::Pending,
        ];
    }
}
