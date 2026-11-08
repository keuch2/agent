<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Support\SupportMode;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportModeController extends Controller
{
    public function store(Request $request, Tenant $tenant, SupportMode $support, AuditLogger $audit): RedirectResponse
    {
        $support->start($request, $tenant);
        $audit->record('support.started', $tenant, tenantId: $tenant->id, supportMode: true);

        return to_route('dashboard');
    }

    public function destroy(Request $request, SupportMode $support, AuditLogger $audit): RedirectResponse
    {
        $current = $support->current($request);

        if ($current !== null) {
            $audit->record('support.ended', Tenant::query()->find($current['id']), tenantId: $current['id'], supportMode: true);
            $support->stop($request);
        }

        return to_route('admin.tenants.index');
    }
}
