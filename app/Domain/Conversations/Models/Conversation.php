<?php

namespace App\Domain\Conversations\Models;

use App\Domain\Channels\Models\Channel;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row is one billing window: see BillableConversationRule.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $contact_id
 * @property int $channel_id
 * @property ConversationStatus $status
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $closed_at
 * @property bool $is_billable
 * @property CarbonImmutable|null $billable_at
 * @property-read Contact $contact
 * @property-read Channel $channel
 */
#[Fillable(['contact_id', 'channel_id', 'status', 'started_at', 'closed_at', 'is_billable', 'billable_at'])]
class Conversation extends Model
{
    use BelongsToTenant;

    protected $attributes = ['status' => 'bot', 'is_billable' => false];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_billable' => 'boolean',
            'billable_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
