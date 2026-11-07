<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

/*
 * Acceptance criterion of phase 1: a user of one tenant cannot see or modify
 * another tenant's data.
 */

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['name' => 'Empresa A']);
    $this->tenantB = Tenant::factory()->create(['name' => 'Empresa B']);
    $this->adminA = tenantUser($this->tenantA);
    $this->userB = tenantUser($this->tenantB);
});

it('lists only users of the own tenant', function () {
    $this->actingAs($this->adminA)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/index')
            ->has('users', 1)
            ->where('users.0.email', $this->adminA->email));
});

it('cannot update a user of another tenant', function () {
    $this->actingAs($this->adminA)
        ->put(route('users.update', $this->userB), ['name' => 'Hackeado', 'email' => 'x@example.com', 'role' => 'advisor'])
        ->assertNotFound();

    expect($this->userB->fresh()->name)->not->toBe('Hackeado');
});

it('cannot delete a user of another tenant', function () {
    $this->actingAs($this->adminA)
        ->delete(route('users.destroy', $this->userB))
        ->assertNotFound();

    expect($this->userB->fresh())->not->toBeNull();
});

it('scopes Eloquent queries to the active tenant', function () {
    $emails = asTenant($this->tenantA, fn () => User::query()->pluck('email')->all());

    expect($emails)->toBe([$this->adminA->email]);
});

it('scopes tenant-owned models and hides other tenants from Tenant queries', function () {
    auditLogFor($this->tenantA, 'a.event');
    auditLogFor($this->tenantB, 'b.event');

    asTenant($this->tenantA, function () {
        expect(AuditLog::query()->pluck('action')->all())->toBe(['a.event'])
            ->and(Tenant::query()->pluck('name')->all())->toBe(['Empresa A'])
            ->and(Tenant::query()->find($this->tenantB->id))->toBeNull();
    });
});

it('shows the own tenant name and not another one on the dashboard', function () {
    $this->actingAs($this->adminA)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.tenant.name', 'Empresa A'));
});

it('denies the platform panel to tenant users', function () {
    $this->actingAs($this->adminA)->get(route('admin.tenants.index'))->assertForbidden();
    $this->actingAs($this->adminA)->get(route('admin.plans.index'))->assertForbidden();
});
