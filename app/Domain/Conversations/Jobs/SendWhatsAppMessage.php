<?php

namespace App\Domain\Conversations\Jobs;

use App\Domain\Channels\Exceptions\MetaApiException;
use App\Domain\Channels\WhatsApp\WhatsAppClient;
use App\Domain\Conversations\BillableConversationRule;
use App\Domain\Conversations\Enums\MessageStatus;
use App\Domain\Conversations\Models\Message;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Meta has no idempotency key, so the message is claimed atomically before the
 * call. A worker that dies mid-call leaves it in `sending` and it is not resent:
 * losing that reply is preferred over answering a customer twice.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 30, 60, 300];

    public function __construct(public readonly int $messageId) {}

    public function handle(WhatsAppClient $client, BillableConversationRule $billing): void
    {
        $claimed = Message::query()
            ->whereKey($this->messageId)
            ->where('status', MessageStatus::Pending)
            ->update(['status' => MessageStatus::Sending]);

        if ($claimed === 0) {
            return;
        }

        $message = Message::query()->with('conversation.contact', 'conversation.channel')->findOrFail($this->messageId);
        $conversation = $message->conversation;
        $channel = $conversation->channel;

        try {
            $externalId = $client->sendText(
                (string) $channel->phone_number_id,
                (string) $channel->access_token,
                (string) $conversation->contact->wa_id,
                (string) $message->content,
            );
        } catch (MetaApiException $e) {
            if ($e->retryable) {
                $message->update(['status' => MessageStatus::Pending, 'error' => $e->getMessage()]);

                throw $e;
            }

            $message->update(['status' => MessageStatus::Failed, 'error' => $e->getMessage()]);

            return;
        }

        $message->update(['status' => MessageStatus::Sent, 'external_id' => $externalId, 'error' => null]);
        $billing->recordReply($conversation, now());
    }

    public function failed(?Throwable $exception): void
    {
        Message::on(TenantContext::ADMIN_CONNECTION)
            ->whereKey($this->messageId)
            ->whereIn('status', [MessageStatus::Pending, MessageStatus::Sending])
            ->update(['status' => MessageStatus::Failed, 'error' => $exception?->getMessage()]);
    }
}
