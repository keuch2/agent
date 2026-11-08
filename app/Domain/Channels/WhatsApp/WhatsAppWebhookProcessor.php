<?php

namespace App\Domain\Channels\WhatsApp;

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Channels\Models\Channel;
use App\Domain\Conversations\Actions\ReceiveWhatsAppMessage;
use App\Domain\Conversations\Enums\MessageStatus;
use App\Domain\Conversations\Models\Message;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Routes each change of a webhook to its tenant by phone_number_id. That lookup
 * crosses tenants, so it alone uses the platform connection; everything after
 * it runs inside the tenant's context, subject to Row Level Security.
 */
final class WhatsAppWebhookProcessor
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReceiveWhatsAppMessage $receiveMessage,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) === 'messages') {
                    $this->processChange($change['value'] ?? []);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function processChange(array $value): void
    {
        $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id');

        $channel = Channel::on(TenantContext::ADMIN_CONNECTION)
            ->with('tenant')
            ->where('type', ChannelType::WhatsApp)
            ->where('status', ChannelStatus::Connected)
            ->where('phone_number_id', $phoneNumberId)
            ->first();

        if ($channel === null) {
            Log::warning('WhatsApp webhook for an unknown or disconnected number.', ['phone_number_id' => $phoneNumberId]);

            return;
        }

        if ($channel->tenant->isSuspended()) {
            return;
        }

        $this->tenantContext->run($channel->tenant_id, function () use ($channel, $value): void {
            $tenantChannel = Channel::query()->findOrFail($channel->id);
            $names = Arr::pluck($value['contacts'] ?? [], 'profile.name', 'wa_id');

            foreach ($value['messages'] ?? [] as $message) {
                $this->receiveMessage->handle($tenantChannel, $message, $names[$message['from']] ?? null);
            }

            foreach ($value['statuses'] ?? [] as $status) {
                $this->applyStatus($status);
            }
        });
    }

    /**
     * Webhooks can arrive out of order: a status never moves backwards.
     *
     * @param  array<string, mixed>  $status
     */
    private function applyStatus(array $status): void
    {
        $new = MessageStatus::tryFrom((string) ($status['status'] ?? ''));
        $message = Message::query()->where('external_id', $status['id'] ?? null)->first();

        if ($new === null || $message === null || ! $new->isAfter($message->status)) {
            return;
        }

        $message->update([
            'status' => $new,
            'error' => Arr::get($status, 'errors.0.error_data.details') ?? Arr::get($status, 'errors.0.title'),
        ]);
    }
}
