<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Exceptions\MissingTenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

it('fails closed when a tenant-scoped model is queried without a tenant', function () {
    AuditLog::query()->get();
})->throws(MissingTenantContext::class);

it('refuses to create a tenant-scoped record without a tenant', function () {
    AuditLog::query()->create(['action' => 'orphan']);
})->throws(MissingTenantContext::class);

it('refuses to create a record for a tenant other than the active one', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    asTenant($tenantA, fn () => AuditLog::query()->create(['action' => 'x', 'tenant_id' => $tenantB->id]));
})->throws(LogicException::class);

it('allows unscoped access only on the platform connection', function () {
    auditLogFor(Tenant::factory()->create());
    auditLogFor(Tenant::factory()->create());

    expect(AuditLog::on(TenantContext::ADMIN_CONNECTION)->count())->toBe(2);
});

it('restores the tenant in queued jobs and clears it afterwards', function () {
    $tenant = Tenant::factory()->create();
    $context = app(TenantContext::class);

    $context->set($tenant->id);
    $payload = Context::dehydrate();

    $context->clear();
    Context::hydrate($payload);

    $setting = fn () => DB::connection(TenantContext::APP_CONNECTION)->scalar("SELECT current_setting('app.tenant_id', true)");

    expect($context->id())->toBe($tenant->id)
        ->and($setting())->toBe((string) $tenant->id);

    Context::hydrate(null);

    expect($context->has())->toBeFalse()
        ->and($setting())->toBe('');
});
