<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * Second isolation barrier behind the Eloquent tenant scope. The runtime role
 * (DB_USERNAME) only sees rows whose tenant matches the `app.tenant_id`
 * session setting, which TenantContext keeps in sync. Unset means no rows.
 */
final class RowLevelSecurity
{
    public static function createCurrentTenantFunction(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION app_current_tenant_id() RETURNS bigint
            LANGUAGE sql STABLE
            AS $$ SELECT nullif(current_setting('app.tenant_id', true), '')::bigint $$
            SQL);
    }

    public static function dropCurrentTenantFunction(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS app_current_tenant_id()');
    }

    public static function enableForTable(string $table, string $column = 'tenant_id'): void
    {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement(<<<SQL
            CREATE POLICY tenant_isolation ON {$table}
                USING ({$column} = app_current_tenant_id())
                WITH CHECK ({$column} = app_current_tenant_id())
            SQL);
    }

    public static function disableForTable(string $table): void
    {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
    }
}
