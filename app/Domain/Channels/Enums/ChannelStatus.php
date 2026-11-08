<?php

namespace App\Domain\Channels\Enums;

enum ChannelStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Error = 'error';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Sin conectar',
            self::Connected => 'Conectado',
            self::Error => 'Con error',
            self::Disconnected => 'Desconectado',
        };
    }
}
