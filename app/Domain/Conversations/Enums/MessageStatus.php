<?php

namespace App\Domain\Conversations\Enums;

enum MessageStatus: string
{
    case Received = 'received';
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';

    public function isAfter(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Received, self::Pending => 0,
            self::Sending => 1,
            self::Sent => 2,
            self::Delivered => 3,
            self::Read => 4,
            self::Failed => 5,
        };
    }
}
