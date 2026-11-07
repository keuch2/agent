<?php

namespace Tests;

use App\Domain\Tenancy\TenantContext;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

/**
 * Feature tests run against PostgreSQL with Row Level Security active, using
 * the runtime and platform roles on separate connections. Transactions cannot
 * span both, so the database is truncated between tests instead of rolled back.
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTruncation;

    /** @var list<string> */
    protected array $connectionsToTruncate = ['pgsql_owner'];

    /** Reference data seeded once by migrate:fresh. */
    protected array $exceptTables = ['plans', 'roles', 'permissions', 'role_has_permissions'];

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--database' => 'pgsql_owner',
            '--seed' => true,
            '--seeder' => ReferenceDataSeeder::class,
        ];
    }

    protected function tearDown(): void
    {
        if ($this->app?->resolved(TenantContext::class)) {
            $this->app->make(TenantContext::class)->clear();
        }

        parent::tearDown();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
