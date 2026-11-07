<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Initial commercial plans. Values are editable afterwards from the
 * superadmin panel; re-running only creates missing plans.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'esencial', 'name' => 'Esencial', 'monthly_price_usd' => 185, 'conversation_quota' => 1000, 'advisor_limit' => 3, 'features' => []],
            ['slug' => 'profesional', 'name' => 'Profesional', 'monthly_price_usd' => 310, 'conversation_quota' => 3000, 'advisor_limit' => 10, 'features' => ['advanced_handoff_rules']],
            ['slug' => 'corporativo', 'name' => 'Corporativo', 'monthly_price_usd' => 540, 'conversation_quota' => 8000, 'advisor_limit' => null, 'features' => ['advanced_handoff_rules', 'integrations']],
        ];

        foreach ($plans as $plan) {
            Plan::query()->firstOrCreate(['slug' => $plan['slug']], $plan + ['overage_price_usd' => 0.04]);
        }
    }
}
