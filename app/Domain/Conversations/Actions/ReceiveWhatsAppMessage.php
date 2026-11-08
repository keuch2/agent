<?php

namespace App\Domain\Conversations\Actions;

use App\Domain\Channels\Models\Channel;
use App\Domain\Conversations\BillableConversationRule;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Enums\MessageAuthor;
use App\Domain\Conversations\Enums\MessageDirection;
use App\Domain\Conversations\Enums\MessageStatus;
use App\Domain\Conversations\Enums\MessageType;
use App\Domain\Conversations\Jobs\SendWhatsAppMessage;
use App\Domain\Conversations\Models\Contact;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\Message;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Records an inbound message and queues its reply, exactly once per external
 * id. The contact row lock serializes concurrent webhooks of the same person,
 * so they cannot open two conversations or answer twice.
 */
final class ReceiveWhatsAppMessage
{
    private const string UNSUPPORTED_NOTICE = 'Por ahora solo puedo leer mensajes de texto.';

    /** Message kinds that are recorded but never answered. */
    private const array SILENT_TYPES = ['reaction', 'system', 'unsupported', 'request_welcome'];

    public function __construct(private readonly BillableConversationRule $billing) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public function handle(Channel $channel, array $message, ?string $profileName): void
    {
        $reply = DB::transaction(function () use ($channel, $message, $profileName): ?Message {
            $occurredAt = CarbonImmutable::createFromTimestampUTC((int) $message['timestamp']);
            $contact = $this->lockedContact((string) $message['from'], $profileName, $occurredAt);

            if (Message::query()->where('external_id', $message['id'])->exists()) {
                return null;
            }

            $conversation = $this->currentConversation($contact, $channel, $occurredAt);
            [$type, $content] = $this->parse($message);

            $conversation->messages()->create([
                'direction' => MessageDirection::Inbound,
                'author' => MessageAuthor::Customer,
                'type' => $type,
                'content' => $content,
                'external_id' => $message['id'],
                'status' => MessageStatus::Received,
                'payload' => $message,
                'occurred_at' => $occurredAt,
            ]);

            $text = $this->replyText((string) ($message['type'] ?? ''), $type, $content);

            return $text === null ? null : $conversation->messages()->create([
                'direction' => MessageDirection::Outbound,
                'author' => MessageAuthor::System,
                'type' => MessageType::Text,
                'content' => $text,
                'status' => MessageStatus::Pending,
                'occurred_at' => now(),
            ]);
        });

        if ($reply !== null) {
            SendWhatsAppMessage::dispatch($reply->id);
        }
    }

    private function lockedContact(string $waId, ?string $name, CarbonImmutable $occurredAt): Contact
    {
        $id = Contact::query()->createOrFirst(['wa_id' => $waId], [
            'name' => $name,
            'phone' => $waId,
            'first_contact_at' => $occurredAt,
            'last_contact_at' => $occurredAt,
        ])->id;

        $contact = Contact::query()->lockForUpdate()->findOrFail($id);

        $contact->update([
            'name' => $name ?? $contact->name,
            'last_contact_at' => $occurredAt->max($contact->last_contact_at),
        ]);

        return $contact;
    }

    private function currentConversation(Contact $contact, Channel $channel, CarbonImmutable $occurredAt): Conversation
    {
        $conversation = Conversation::query()
            ->where('contact_id', $contact->id)
            ->where('channel_id', $channel->id)
            ->where('status', '!=', ConversationStatus::Closed)
            ->first();

        if ($conversation !== null && $this->billing->isWithinWindow($conversation, $occurredAt)) {
            return $conversation;
        }

        $conversation?->update([
            'status' => ConversationStatus::Closed,
            'closed_at' => $this->billing->windowEndsAt($conversation),
        ]);

        return Conversation::query()->create([
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'started_at' => $occurredAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{MessageType, string|null}
     */
    private function parse(array $message): array
    {
        return match ($message['type'] ?? null) {
            'text' => [MessageType::Text, Arr::get($message, 'text.body')],
            'button' => [MessageType::Button, Arr::get($message, 'button.text')],
            'interactive' => [MessageType::Button, Arr::get($message, 'interactive.button_reply.title') ?? Arr::get($message, 'interactive.list_reply.title')],
            'image' => [MessageType::Image, Arr::get($message, 'image.caption')],
            'audio' => [MessageType::Audio, null],
            'document' => [MessageType::Document, Arr::get($message, 'document.caption')],
            'location' => [MessageType::Location, implode(',', [Arr::get($message, 'location.latitude'), Arr::get($message, 'location.longitude')])],
            default => [MessageType::Other, null],
        };
    }

    /**
     * Phase 2 echo; the AI replaces this in phase 3.
     */
    private function replyText(string $rawType, MessageType $type, ?string $content): ?string
    {
        if (in_array($rawType, self::SILENT_TYPES, true)) {
            return null;
        }

        return in_array($type, [MessageType::Text, MessageType::Button], true) && filled($content)
            ? "Eco: {$content}"
            : self::UNSUPPORTED_NOTICE;
    }
}
