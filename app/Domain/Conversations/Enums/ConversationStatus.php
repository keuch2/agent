<?php

namespace App\Domain\Conversations\Enums;

enum ConversationStatus: string
{
    case Bot = 'bot';
    case HandedOff = 'handed_off';
    case Human = 'human';
    case Closed = 'closed';
}
