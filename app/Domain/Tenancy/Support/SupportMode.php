<?php

namespace App\Domain\Tenancy\Support;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Http\Request;

/**
 * A superadmin working inside a tenant's panel. Chosen in the session and only
 * applied on tenant routes, where the request runs on the runtime connection
 * with that tenant's context, so Row Level Security still applies.
 */
final class SupportMode
{
    private const string SESSION_KEY = 'support_tenant';

    public const string REQUEST_ATTRIBUTE = 'support_mode';

    public function start(Request $request, Tenant $tenant): void
    {
        $request->session()->put(self::SESSION_KEY, $tenant->only(['id', 'name']));
    }

    public function stop(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array{id: int, name: string}|null
     */
    public function current(Request $request): ?array
    {
        return $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
    }

    public function isApplied(Request $request): bool
    {
        return $request->attributes->getBoolean(self::REQUEST_ATTRIBUTE);
    }
}
