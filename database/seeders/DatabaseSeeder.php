<?php

namespace Database\Seeders;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local development data. Run with: php artisan db:seed --database=pgsql_admin
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        User::factory()->superadmin()->create([
            'name' => 'Mister Co.',
            'email' => 'superadmin@misterco.test',
        ]);

        foreach (['Empresa Demo' => 'demo', 'Otra Empresa' => 'otra'] as $name => $slug) {
            $tenant = Tenant::factory()->create(['name' => $name, 'status' => TenantStatus::Active]);

            User::factory()->for($tenant)->withRole(TenantRole::Admin)->create([
                'name' => "Admin {$name}",
                'email' => "admin@{$slug}.test",
            ]);
            User::factory()->for($tenant)->withRole(TenantRole::Advisor)->create([
                'email' => "asesor@{$slug}.test",
            ]);
        }
    }
}
