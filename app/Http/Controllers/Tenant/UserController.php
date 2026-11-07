<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\Actions\InviteTenantUser;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TenantUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel users of the current tenant. Queries are tenant-scoped by
 * IdentifyTenant; route model binding of {user} is scoped the same way.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->tenantRole()?->value,
                'role_label' => $user->tenantRole()?->label(),
                'two_factor' => $user->hasConfirmedTwoFactor(),
                'is_current' => $user->is($request->user()),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => TenantRole::options(),
        ]);
    }

    public function store(TenantUserRequest $request, InviteTenantUser $invite, AuditLogger $audit): RedirectResponse
    {
        $user = $invite->handle(
            $request->user()->tenant_id,
            $request->validated('name'),
            $request->validated('email'),
            TenantRole::from($request->validated('role')),
        );

        $audit->record('user.created', $user, new: $user->only(['name', 'email']) + ['role' => $request->validated('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Usuario creado. Enviamos el acceso a {$user->email}."]);

        return back();
    }

    public function update(TenantUserRequest $request, User $user, InviteTenantUser $invite, AuditLogger $audit): RedirectResponse
    {
        $previousRole = $user->tenantRole()?->value;

        $user->update($request->safe()->only(['name', 'email']));

        $old = $user->getPrevious();
        $changes = $user->getChanges();

        if ($previousRole !== $request->validated('role')) {
            $invite->assignRole($user, TenantRole::from($request->validated('role')));
            $old['role'] = $previousRole;
            $changes['role'] = $request->validated('role');
        }

        unset($changes['updated_at'], $old['updated_at']);

        if ($changes !== []) {
            $audit->record('user.updated', $user, array_intersect_key($old, $changes), $changes);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return back();
    }

    public function destroy(User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();
        $audit->record('user.deleted', $user, old: $user->only(['name', 'email']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return back();
    }
}
