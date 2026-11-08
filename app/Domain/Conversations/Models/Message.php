<?php

namespace App\Domain\Conversations\Models;

use App\Domain\Conversations\Enums\MessageAuthor;
use App\Domain\Conversations\Enums\MessageDirection;
use App\Domain\Conversations\Enums\MessageStatus;
use App\Domain\Conversations\Enums\MessageType;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $conversation_id
 * @property MessageDirection $direction
 * @property MessageAuthor $author
 * @property MessageType $type
 * @property string|null $content
 * @property string|null $external_id
 * @property MessageStatus $status
 * @property string|null $error
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $occurred_at
 * @property-read Conversation $conversation
 */
#[Fillable(['conversation_id', 'direction', 'author', 'type', 'content', 'external_id', 'status', 'error', 'payload', 'occurred_at'])]
class Message extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'author' => MessageAuthor::class,
            'type' => MessageType::class,
            'status' => MessageStatus::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
