<?php

namespace App\Domain\Conversations\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Document = 'document';
    case Location = 'location';
    case Button = 'button';
    case Template = 'template';
    case Other = 'other';
}
