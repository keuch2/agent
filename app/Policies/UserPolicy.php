<?php

namespace App\Policies;

use App\Domain\Tenancy\Enums\TenantPermission;
use App\Models\User;

/**
 * Management of a tenant's panel users by that tenant's administrators.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->tenant_id !== null && $actor->can(TenantPermission::ManageUsers->value);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->viewAny($actor) && $this->sameTenant($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && $actor->isNot($target);
    }

    private function sameTenant(User $actor, User $target): bool
    {
        return $target->tenant_id !== null && $target->tenant_id === $actor->tenant_id;
    }
}
