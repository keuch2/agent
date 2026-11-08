<?php

namespace App\Domain\Channels\WhatsApp;

use App\Domain\Channels\Exceptions\MetaApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Cloud API. Every call uses the access token of the tenant's channel.
 */
final class WhatsAppClient
{
    /**
     * @return array{display_phone_number?: string, verified_name?: string}
     */
    public function phoneNumber(string $phoneNumberId, string $token): array
    {
        return $this->request('GET', $phoneNumberId, $token, ['fields' => 'display_phone_number,verified_name'])->json();
    }

    /**
     * Makes the account's webhooks reach this app.
     */
    public function subscribeApp(string $wabaId, string $token): void
    {
        $this->request('POST', "{$wabaId}/subscribed_apps", $token);
    }

    /**
     * @return string the external message id
     */
    public function sendText(string $phoneNumberId, string $token, string $to, string $body): string
    {
        $response = $this->request('POST', "{$phoneNumberId}/messages", $token, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);

        return (string) $response->json('messages.0.id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function request(string $method, string $path, string $token, array $data = []): Response
    {
        $version = config('services.meta.graph_version');

        try {
            $response = Http::baseUrl("https://graph.facebook.com/{$version}")
                ->withToken($token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->send($method, $path, $method === 'GET' ? ['query' => $data] : ['json' => $data]);
        } catch (ConnectionException) {
            throw new MetaApiException('No se pudo conectar con Meta.', retryable: true);
        }

        if ($response->failed()) {
            throw new MetaApiException(
                (string) ($response->json('error.error_user_msg') ?? $response->json('error.message') ?? "Error {$response->status()} de Meta."),
                retryable: $response->serverError() || $response->tooManyRequests() || $response->json('error.code') === 130429,
            );
        }

        return $response;
    }
}
