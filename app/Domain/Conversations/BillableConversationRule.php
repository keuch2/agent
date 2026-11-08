<?php

namespace App\Domain\Conversations;

use App\Domain\Conversations\Enums\MessageDirection;
use App\Domain\Conversations\Models\Conversation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The single definition of a billable conversation: a thread with a contact on
 * a channel with at least one inbound message and one reply (AI, advisor or
 * system) inside a fixed window of 24 hours from the first inbound message.
 * Later messages do not extend the window. It counts in the month it started.
 */
final class BillableConversationRule
{
    public const int WINDOW_HOURS = 24;

    public function windowEndsAt(Conversation $conversation): CarbonImmutable
    {
        return $conversation->started_at->addHours(self::WINDOW_HOURS);
    }

    public function isWithinWindow(Conversation $conversation, CarbonInterface $moment): bool
    {
        return $moment->lt($this->windowEndsAt($conversation));
    }

    /**
     * Call when a reply has actually gone out.
     */
    public function recordReply(Conversation $conversation, CarbonInterface $repliedAt): void
    {
        if ($conversation->is_billable
            || ! $this->isWithinWindow($conversation, $repliedAt)
            || ! $conversation->messages()->where('direction', MessageDirection::Inbound)->exists()) {
            return;
        }

        $conversation->update(['is_billable' => true, 'billable_at' => $repliedAt]);
    }
}
