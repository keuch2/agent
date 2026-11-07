<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Billing\Models\Plan;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('requires two-factor authentication for superadmins', function () {
    $this->actingAs(superadmin(withTwoFactor: false))
        ->get(route('admin.tenants.index'))
        ->assertRedirect(route('security.edit'));
});

it('lists all tenants for a superadmin', function () {
    Tenant::factory()->count(2)->create();

    $this->actingAs(superadmin())
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/tenants/index')->has('tenants.data', 2));
});

it('sends superadmins from the tenant panel to the platform panel', function () {
    $this->actingAs(superadmin())
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.tenants.index'));
});

it('creates a tenant with its first administrator', function () {
    Notification::fake();
    $plan = Plan::query()->where('slug', 'profesional')->firstOrFail();

    $this->actingAs(superadmin())
        ->post(route('admin.tenants.store'), [
            'name' => 'Comercial Asunción',
            'legal_name' => 'Comercial Asunción S.A.',
            'ruc' => '80012345-6',
            'timezone' => 'America/Asuncion',
            'plan_id' => $plan->id,
            'admin_name' => 'Ana Benítez',
            'admin_email' => 'Ana@Comercial.com.py',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $tenant = Tenant::on(TenantContext::ADMIN_CONNECTION)->where('ruc', '80012345-6')->sole();
    $admin = User::query()->where('email', 'ana@comercial.com.py')->sole();

    setPermissionsTeamId($tenant->id);

    expect($tenant->status)->toBe(TenantStatus::Onboarding)
        ->and($tenant->plan_id)->toBe($plan->id)
        ->and($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->hasRole(TenantRole::Admin->value))->toBeTrue()
        ->and(AuditLog::on(TenantContext::ADMIN_CONNECTION)->where('action', 'tenant.created')->value('tenant_id'))->toBe($tenant->id);

    Notification::assertSentTo($admin, ResetPassword::class);
});

it('validates the RUC format and unique admin email', function () {
    $existing = tenantUser();

    $this->actingAs(superadmin())
        ->post(route('admin.tenants.store'), [
            'name' => 'X',
            'ruc' => '123',
            'timezone' => 'America/Asuncion',
            'plan_id' => Plan::query()->value('id'),
            'admin_name' => 'Y',
            'admin_email' => strtoupper($existing->email),
        ])
        ->assertSessionHasErrors(['ruc', 'admin_email']);
});

it('updates a tenant status and records who changed it', function () {
    $tenant = Tenant::factory()->create(['status' => TenantStatus::Onboarding]);
    $superadmin = superadmin();

    $this->actingAs($superadmin)
        ->put(route('admin.tenants.update', $tenant), [
            'name' => $tenant->name,
            'timezone' => 'America/Asuncion',
            'plan_id' => $tenant->plan_id,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors();

    $log = AuditLog::on(TenantContext::ADMIN_CONNECTION)->where('action', 'tenant.updated')->sole();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active)
        ->and($tenant->fresh()->activated_at)->not->toBeNull()
        ->and($log->user_id)->toBe($superadmin->id)
        ->and($log->old_values)->toBe(['status' => 'onboarding', 'activated_at' => null])
        ->and($log->new_values)->toHaveKeys(['status', 'activated_at'])
        ->and($log->new_values['status'])->toBe('active');
});

it('blocks users of a suspended tenant', function () {
    $user = tenantUser(Tenant::factory()->suspended()->create());

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
