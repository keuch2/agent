<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Notifications\TenantUserInvitation;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Creates a user inside a tenant with a random password and emails them an
 * invitation to set their own. Accounts are never self-registered.
 */
final class InviteTenantUser
{
    public function handle(int $tenantId, string $name, string $email, TenantRole $role): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(40),
        ]);
        $user->tenant_id = $tenantId;
        $user->save();

        $this->assignRole($user, $role);

        DB::afterCommit(function () use ($user): void {
            /** @var PasswordBroker $broker */
            $broker = Password::broker();
            $user->notify(new TenantUserInvitation($broker->createToken($user), $user->tenant->name));
        });

        return $user;
    }

    public function assignRole(User $user, TenantRole $role): void
    {
        $previousTeam = getPermissionsTeamId();
        setPermissionsTeamId($user->tenant_id);

        try {
            $user->syncRoles([$role->value]);
            $user->unsetRelation('roles');
        } finally {
            setPermissionsTeamId($previousTeam);
        }
    }
}
