<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Platform action (superadmin panel): creates the tenant and its first admin.
 */
final class CreateTenant
{
    public function __construct(
        private readonly InviteTenantUser $inviteTenantUser,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  validated tenant attributes
     * @param  array{name: string, email: string}  $admin
     */
    public function handle(array $attributes, array $admin): Tenant
    {
        return DB::transaction(function () use ($attributes, $admin): Tenant {
            $tenant = Tenant::query()->create($attributes);

            $this->inviteTenantUser->handle($tenant->id, $admin['name'], $admin['email'], TenantRole::Admin);

            $this->audit->record('tenant.created', $tenant, new: $tenant->only(array_keys($attributes)), tenantId: $tenant->id);

            return $tenant;
        });
    }
}
