<?php

use App\Domain\Billing\Models\Plan;

it('seeds the three commercial plans with their limits', function () {
    $plans = Plan::query()->orderBy('monthly_price_usd')->get()
        ->map(fn (Plan $p) => [$p->slug, $p->monthly_price_usd, $p->conversation_quota, $p->advisor_limit, $p->overage_price_usd])
        ->all();

    expect($plans)->toBe([
        ['esencial', '185.00', 1000, 3, '0.0400'],
        ['profesional', '310.00', 3000, 10, '0.0400'],
        ['corporativo', '540.00', 8000, null, '0.0400'],
    ]);
});

it('lets a superadmin edit a plan, including unlimited advisors and no features', function () {
    $plan = Plan::query()->where('slug', 'profesional')->firstOrFail();

    $this->actingAs(superadmin())
        ->put(route('admin.plans.update', $plan), [
            'name' => 'Profesional',
            'monthly_price_usd' => '320',
            'conversation_quota' => 3500,
            'advisor_limit' => null,
            'overage_price_usd' => '0.035',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.plans.index'));

    $plan->refresh();

    expect([$plan->monthly_price_usd, $plan->conversation_quota, $plan->advisor_limit, $plan->features])
        ->toBe(['320.00', 3500, null, []]);

    // Restore seeded values: the plans table is not truncated between tests.
    $plan->update(['monthly_price_usd' => 310, 'conversation_quota' => 3000, 'advisor_limit' => 10, 'overage_price_usd' => 0.04, 'features' => ['advanced_handoff_rules']]);
});

it('rejects unknown plan features', function () {
    $plan = Plan::query()->firstOrFail();

    $this->actingAs(superadmin())
        ->put(route('admin.plans.update', $plan), [
            'name' => $plan->name,
            'monthly_price_usd' => 1,
            'conversation_quota' => 1,
            'overage_price_usd' => 0,
            'features' => ['free_lunch'],
        ])
        ->assertSessionHasErrors('features.0');
});
