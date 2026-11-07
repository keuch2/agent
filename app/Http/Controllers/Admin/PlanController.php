<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Billing\Models\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Plan::class);

        return Inertia::render('admin/plans/index', [
            'plans' => Plan::query()->withCount('tenants')->orderBy('monthly_price_usd')->get(),
            'features' => Plan::FEATURES,
        ]);
    }

    public function edit(Plan $plan): Response
    {
        Gate::authorize('update', $plan);

        return Inertia::render('admin/plans/edit', [
            'plan' => $plan,
            'features' => Plan::FEATURES,
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan, AuditLogger $audit): RedirectResponse
    {
        $plan->update($request->validated() + ['features' => []]);
        $audit->recordChanges('plan.updated', $plan);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Plan {$plan->name} actualizado."]);

        return to_route('admin.plans.index');
    }
}
