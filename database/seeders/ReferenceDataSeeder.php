<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data every environment needs (production included): plans and roles.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PlanSeeder::class, RoleSeeder::class]);
    }
}
