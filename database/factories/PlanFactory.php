<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->unique()->word(),
            'monthly_price_usd' => 185,
            'conversation_quota' => 1000,
            'advisor_limit' => 3,
            'overage_price_usd' => 0.04,
            'features' => [],
        ];
    }
}
