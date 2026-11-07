<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_superadmin;
    }

    public function create(User $actor): bool
    {
        return $actor->is_superadmin;
    }

    public function update(User $actor, Tenant $tenant): bool
    {
        return $actor->is_superadmin;
    }
}
