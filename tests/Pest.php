<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

function tenantUser(?Tenant $tenant = null, TenantRole $role = TenantRole::Admin): User
{
    return User::factory()
        ->for($tenant ?? Tenant::factory()->create())
        ->withRole($role)
        ->create();
}

function superadmin(bool $withTwoFactor = true): User
{
    $factory = User::factory()->superadmin();

    return ($withTwoFactor ? $factory->withTwoFactor() : $factory)->create();
}

/**
 * @template T
 *
 * @param  callable(): T  $callback
 * @return T
 */
function asTenant(Tenant $tenant, callable $callback): mixed
{
    return app(TenantContext::class)->run($tenant->id, $callback);
}

function auditLogFor(Tenant $tenant, string $action = 'test.event'): AuditLog
{
    return asTenant($tenant, fn () => AuditLog::query()->create(['action' => $action]));
}
