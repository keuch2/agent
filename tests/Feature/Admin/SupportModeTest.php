<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function platformAudit(string $action): ?AuditLog
{
    return AuditLog::on(TenantContext::ADMIN_CONNECTION)->where('action', $action)->first();
}

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->member = tenantUser($this->tenant);
    $this->other = Tenant::factory()->create(['name' => 'Otra Empresa']);
    $this->outsider = tenantUser($this->other);
    $this->superadmin = superadmin();
});

it('starts support mode, opens the tenant panel and audits it', function () {
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant))->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk();

    $log = platformAudit('support.started');

    expect($log->support_mode)->toBeTrue()->and($log->tenant_id)->toBe($this->tenant->id);
});

it('sees only the supported tenant', function () {
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant));

    $this->get(route('users.index'))
        ->assertOk()
        ->assertSee($this->member->email)
        ->assertDontSee($this->outsider->email);
});

it('cannot reach another tenant record through support mode', function () {
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant));

    $this->delete(route('users.destroy', $this->outsider))->assertNotFound();
});

it('creates users in the supported tenant and marks the audit as support', function () {
    Notification::fake();
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant));

    $this->post(route('users.store'), ['name' => 'Nuevo', 'email' => 'nuevo@demo.test', 'role' => 'advisor'])->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'nuevo@demo.test')->value('tenant_id'))->toBe($this->tenant->id)
        ->and(platformAudit('user.created')->support_mode)->toBeTrue();
});

it('can enter a suspended tenant', function () {
    $suspended = Tenant::factory()->suspended()->create();

    $this->actingAs($this->superadmin)->post(route('admin.support.store', $suspended));

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($this->superadmin);
});

it('keeps the platform panel unfiltered while in support mode', function () {
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant));

    $this->get(route('admin.tenants.index'))->assertOk()->assertSee($this->other->name);
});

it('ends support mode and audits it', function () {
    $this->actingAs($this->superadmin)->post(route('admin.support.store', $this->tenant));

    $this->delete(route('admin.support.destroy'))->assertRedirect(route('admin.tenants.index'));
    $this->get(route('dashboard'))->assertRedirect(route('admin.tenants.index'));

    expect(platformAudit('support.ended')->tenant_id)->toBe($this->tenant->id);
});

it('is unavailable to tenant users', function () {
    $this->actingAs($this->member)->post(route('admin.support.store', $this->other))->assertForbidden();
});

it('requires two-factor authentication', function () {
    $this->actingAs(superadmin(withTwoFactor: false))->post(route('admin.support.store', $this->tenant))
        ->assertRedirect(route('security.edit'));
});

it('does not grant support rights to a regular user carrying the session key', function () {
    $this->actingAs($this->member)->withSession(['support_tenant' => ['id' => $this->other->id, 'name' => 'x']]);

    $this->get(route('users.index'))->assertOk()->assertDontSee($this->outsider->email);
});
