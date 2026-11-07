<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * Second barrier: even raw SQL on the runtime connection, bypassing Eloquent,
 * only reaches the active tenant's rows.
 */

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create();
    $this->tenantB = Tenant::factory()->create();
    auditLogFor($this->tenantA);
    auditLogFor($this->tenantB);
});

function appDb(): Connection
{
    return DB::connection(TenantContext::APP_CONNECTION);
}

it('returns no rows without an active tenant', function () {
    expect(appDb()->table('audit_logs')->count())->toBe(0)
        ->and(appDb()->table('tenants')->count())->toBe(0);
});

it('returns only the active tenant rows on raw queries', function () {
    asTenant($this->tenantA, function () {
        expect(appDb()->table('audit_logs')->pluck('tenant_id')->all())->toBe([$this->tenantA->id])
            ->and(appDb()->table('tenants')->pluck('id')->all())->toBe([$this->tenantA->id]);
    });
});

it('rejects writing rows for another tenant', function () {
    asTenant($this->tenantA, fn () => appDb()->table('audit_logs')->insert([
        'tenant_id' => $this->tenantB->id,
        'action' => 'forged',
    ]));
})->throws(QueryException::class, 'row-level security');

it('cannot update or delete another tenant rows', function () {
    asTenant($this->tenantA, function () {
        expect(appDb()->table('audit_logs')->where('tenant_id', $this->tenantB->id)->update(['action' => 'x']))->toBe(0)
            ->and(appDb()->table('audit_logs')->where('tenant_id', $this->tenantB->id)->delete())->toBe(0);
    });

    expect(DB::connection(TenantContext::ADMIN_CONNECTION)->table('audit_logs')->count())->toBe(2);
});

it('enables row level security on every table with a tenant_id column', function () {
    $identityTables = ['users', 'roles', 'model_has_roles', 'model_has_permissions'];

    $unprotected = DB::connection('pgsql_owner')->select(<<<'SQL'
        SELECT c.relname AS table_name
        FROM information_schema.columns col
        JOIN pg_class c ON c.relname = col.table_name AND c.relkind = 'r'
        JOIN pg_namespace n ON n.oid = c.relnamespace AND n.nspname = col.table_schema
        WHERE col.table_schema = 'public' AND col.column_name = 'tenant_id' AND NOT c.relrowsecurity
        SQL);

    expect(array_diff(array_column($unprotected, 'table_name'), $identityTables))->toBe([]);
});
