<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Channels\WhatsApp\ProcessWhatsAppWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta's subscription handshake.
     */
    public function verify(Request $request): Response
    {
        $expected = (string) config('services.meta.webhook_verify_token');
        $token = (string) $request->query('hub_verify_token');

        abort_unless(
            $expected !== '' && $request->query('hub_mode') === 'subscribe' && hash_equals($expected, $token),
            Response::HTTP_FORBIDDEN,
        );

        return response((string) $request->query('hub_challenge'), Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    public function receive(Request $request): Response
    {
        ProcessWhatsAppWebhook::dispatch($request->json()->all());

        return response('EVENT_RECEIVED', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
