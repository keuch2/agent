<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Billing\Models\Plan;
use App\Domain\Tenancy\Actions\CreateTenant;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenants = Tenant::query()
            ->with('plan:id,name')
            ->withCount('users')
            ->latest()
            ->paginate(25)
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'ruc' => $tenant->ruc,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'plan' => $tenant->plan->name,
                'users_count' => $tenant->users_count,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/tenants/index', ['tenants' => $tenants]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Tenant::class);

        return Inertia::render('admin/tenants/form', $this->formOptions());
    }

    public function store(TenantRequest $request, CreateTenant $createTenant): RedirectResponse
    {
        $tenant = $createTenant->handle(
            $request->safe()->only(['name', 'legal_name', 'ruc', 'timezone', 'plan_id']),
            ['name' => $request->validated('admin_name'), 'email' => $request->validated('admin_email')],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Empresa creada. Enviamos el acceso a {$request->validated('admin_email')}."]);

        return to_route('admin.tenants.edit', $tenant);
    }

    public function edit(Tenant $tenant): Response
    {
        Gate::authorize('update', $tenant);

        return Inertia::render('admin/tenants/form', [
            ...$this->formOptions(),
            'tenant' => $tenant->only(['id', 'name', 'legal_name', 'ruc', 'timezone', 'plan_id']) + ['status' => $tenant->status->value],
        ]);
    }

    public function update(TenantRequest $request, Tenant $tenant, AuditLogger $audit): RedirectResponse
    {
        $tenant->fill($request->validated());

        if ($tenant->isDirty('status') && $tenant->status === TenantStatus::Active) {
            $tenant->activated_at ??= now();
        }

        $tenant->save();
        $audit->recordChanges('tenant.updated', $tenant, tenantId: $tenant->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa actualizada.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'plans' => Plan::query()->where('is_active', true)->orderBy('monthly_price_usd')->get(['id', 'name', 'monthly_price_usd']),
            'statuses' => TenantStatus::options(),
            'timezones' => timezone_identifiers_list(),
        ];
    }
}
