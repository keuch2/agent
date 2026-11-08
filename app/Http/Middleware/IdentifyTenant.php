<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Support\SupportMode;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Activates the authenticated user's tenant for the rest of the request:
 * Eloquent scope, Row Level Security session setting and permission team.
 */
class IdentifyTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SupportMode $support,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->tenant_id !== null) {
            $this->tenantContext->set($user->tenant_id);
            setPermissionsTeamId($user->tenant_id);
        }

        if ($user instanceof User && $user->is_superadmin) {
            $this->applySupportMode($request, $user);
        }

        return $next($request);
    }

    private function applySupportMode(Request $request, User $user): void
    {
        $target = $this->support->current($request);

        if ($target === null
            || ! $user->hasConfirmedTwoFactor()
            || ! in_array('tenant', $request->route()?->gatherMiddleware() ?? [], true)) {
            return;
        }

        $this->tenantContext->set($target['id']);
        setPermissionsTeamId($target['id']);
        $request->attributes->set(SupportMode::REQUEST_ATTRIBUTE, true);
    }
}
