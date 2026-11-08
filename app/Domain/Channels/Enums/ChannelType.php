<?php

namespace App\Domain\Channels\Enums;

enum ChannelType: string
{
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
}
