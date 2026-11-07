<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the Mister Co. platform panel: superadmins only, with two-factor
 * authentication confirmed, on the platform (BYPASSRLS) connection.
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->is_superadmin, Response::HTTP_FORBIDDEN);

        if (! $user->hasConfirmedTwoFactor()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'Activá la autenticación en dos pasos para acceder al panel de Mister Co.']);

            return redirect()->route('security.edit');
        }

        DB::setDefaultConnection(TenantContext::ADMIN_CONNECTION);

        return $next($request);
    }
}
