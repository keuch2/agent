<?php

namespace App\Domain\Billing\Policies;

use App\Domain\Billing\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_superadmin;
    }

    public function update(User $actor, Plan $plan): bool
    {
        return $actor->is_superadmin;
    }
}
