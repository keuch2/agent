<?php

namespace Database\Seeders;

use App\Domain\Tenancy\Enums\TenantPermission;
use App\Domain\Tenancy\Enums\TenantRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Global role templates (tenant_id = null), assignable inside any tenant.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId(null);

        foreach (TenantPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (TenantRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web')
                ->syncPermissions(array_map(fn (TenantPermission $p) => $p->value, $role->permissions()));
        }
    }
}
