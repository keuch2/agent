<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Plan;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tenants are created on the platform connection: Row Level Security does not
 * let the runtime role insert a tenant other than the active one.
 *
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function configure(): static
    {
        return $this->connection(TenantContext::ADMIN_CONNECTION);
    }

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company().' S.A.',
            'ruc' => fake()->unique()->numerify('800#####-#'),
            'timezone' => 'America/Asuncion',
            'status' => TenantStatus::Active,
            'plan_id' => fn () => Plan::query()->where('slug', 'esencial')->value('id') ?? Plan::factory(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => TenantStatus::Suspended]);
    }
}
