<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * The platform's SMTP account (one row), edited from the Mister Co. panel.
 *
 * @property int $id
 * @property bool $is_enabled
 * @property string $host
 * @property int $port
 * @property string $encryption tls|ssl|none
 * @property string|null $username
 * @property string|null $password
 * @property string $from_address
 * @property string $from_name
 */
#[Fillable(['is_enabled', 'host', 'port', 'encryption', 'username', 'password', 'from_address', 'from_name'])]
#[Hidden(['password'])]
class MailSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    public static function current(): ?self
    {
        return self::query()->first();
    }
}
