<?php

namespace App\Http\Middleware;

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
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->tenant_id !== null) {
            $this->tenantContext->set($user->tenant_id);
            setPermissionsTeamId($user->tenant_id);
        }

        return $next($request);
    }
}
