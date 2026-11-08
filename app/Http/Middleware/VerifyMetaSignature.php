<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects any webhook whose X-Hub-Signature-256 does not match the raw body
 * signed with the Meta app secret. Fails closed when no secret is configured.
 */
class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.meta.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        abort_if($secret === '' || $signature === '', Response::HTTP_FORBIDDEN);

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, $signature), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
