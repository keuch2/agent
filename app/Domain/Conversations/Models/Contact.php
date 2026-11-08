<?php

namespace App\Domain\Conversations\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string|null $name
 * @property string|null $phone
 * @property string|null $wa_id
 * @property list<string> $tags
 * @property string|null $notes
 * @property CarbonImmutable|null $first_contact_at
 * @property CarbonImmutable|null $last_contact_at
 */
#[Fillable(['name', 'phone', 'wa_id', 'tags', 'notes', 'first_contact_at', 'last_contact_at'])]
class Contact extends Model
{
    use BelongsToTenant;

    protected $attributes = ['tags' => '[]'];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'first_contact_at' => 'datetime',
            'last_contact_at' => 'datetime',
        ];
    }
}
