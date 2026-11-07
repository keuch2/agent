<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = tenantUser($this->tenant);
});

it('creates a user in the admin tenant and invites them by email', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('users.store'), ['name' => 'Luis Gómez', 'email' => 'luis@example.com', 'role' => 'advisor'])
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'luis@example.com')->sole();
    setPermissionsTeamId($this->tenant->id);

    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and($user->hasRole(TenantRole::Advisor->value))->toBeTrue()
        ->and(asTenant($this->tenant, fn () => AuditLog::query()->where('action', 'user.created')->count()))->toBe(1);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('changes the role of a user', function () {
    $advisor = tenantUser($this->tenant, TenantRole::Advisor);

    $this->actingAs($this->admin)
        ->put(route('users.update', $advisor), ['name' => $advisor->name, 'email' => $advisor->email, 'role' => 'supervisor'])
        ->assertSessionHasNoErrors();

    $log = asTenant($this->tenant, fn () => AuditLog::query()->where('action', 'user.updated')->sole());

    expect($advisor->fresh()->load('roles')->tenantRole())->toBe(TenantRole::Supervisor)
        ->and($log->new_values)->toBe(['role' => 'supervisor']);
});

it('does not let an admin demote or delete themselves', function () {
    $this->actingAs($this->admin)
        ->put(route('users.update', $this->admin), ['name' => 'Yo', 'email' => $this->admin->email, 'role' => 'advisor'])
        ->assertSessionHasErrors('role');

    $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))->assertForbidden();
});

it('soft deletes a user, who can no longer log in', function () {
    $advisor = tenantUser($this->tenant, TenantRole::Advisor);

    $this->actingAs($this->admin)->delete(route('users.destroy', $advisor))->assertRedirect();

    expect(User::query()->find($advisor->id))->toBeNull()
        ->and(User::withTrashed()->find($advisor->id))->not->toBeNull();
});

it('forbids user management to advisors and supervisors', function (TenantRole $role) {
    $user = tenantUser($this->tenant, $role);

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($user)->post(route('users.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'tenant_admin'])->assertForbidden();
})->with([TenantRole::Advisor, TenantRole::Supervisor]);
