<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Exceptions\MissingTenantContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

/**
 * The tenant the current request or job acts on. Kept in sync with:
 *  - Laravel Context (hidden), so queued jobs restore it automatically;
 *  - the `app.tenant_id` setting of the runtime DB session, read by RLS policies.
 */
final class TenantContext
{
    /** Runtime connection, subject to Row Level Security. */
    public const string APP_CONNECTION = 'pgsql';

    /** Platform connection (BYPASSRLS), used by the superadmin panel and cross-tenant jobs. */
    public const string ADMIN_CONNECTION = 'pgsql_admin';

    private const string CONTEXT_KEY = 'tenant_id';

    private ?int $tenantId = null;

    public function set(int $tenantId): void
    {
        $this->tenantId = $tenantId;
        Context::addHidden(self::CONTEXT_KEY, $tenantId);
        $this->syncDatabaseSession();
    }

    public function clear(): void
    {
        $this->tenantId = null;
        Context::forgetHidden(self::CONTEXT_KEY);
        $this->syncDatabaseSession();
    }

    /**
     * Restore the tenant carried by a queued job's Context (or clear it).
     */
    public function restoreFromContext(): void
    {
        $tenantId = Context::getHidden(self::CONTEXT_KEY);

        is_int($tenantId) ? $this->set($tenantId) : $this->clear();
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    public function idOrFail(): int
    {
        return $this->tenantId ?? throw new MissingTenantContext;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(int $tenantId, callable $callback): mixed
    {
        $previous = $this->tenantId;
        $this->set($tenantId);

        try {
            return $callback();
        } finally {
            $previous === null ? $this->clear() : $this->set($previous);
        }
    }

    private function syncDatabaseSession(): void
    {
        DB::connection(self::APP_CONNECTION)->statement(
            "SELECT set_config('app.tenant_id', ?, false)",
            [(string) $this->tenantId],
        );
    }
}
