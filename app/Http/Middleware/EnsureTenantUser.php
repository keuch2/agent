<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the tenant panel. Superadmins are sent to the platform panel.
 */
class EnsureTenantUser
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->is_superadmin) {
            return redirect()->route('admin.tenants.index');
        }

        $tenant = $this->tenantContext->has() ? Tenant::query()->find($this->tenantContext->id()) : null;

        if ($tenant === null || $tenant->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'La cuenta de tu empresa está suspendida. Contactá a Mister Co.');
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
