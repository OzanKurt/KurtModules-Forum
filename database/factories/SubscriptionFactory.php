<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Forum;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Forum\Models\Subscription;
use Kurt\Modules\Forum\Models\Thread;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /** @var class-string<Subscription> */
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscribable_type' => Thread::class,
        ];
    }
}
