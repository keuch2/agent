<?php

namespace App\Domain\Conversations\Enums;

enum MessageAuthor: string
{
    case Customer = 'customer';
    case Ai = 'ai';
    case Advisor = 'advisor';
    case System = 'system';
}
