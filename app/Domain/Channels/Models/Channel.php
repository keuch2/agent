<?php

namespace App\Domain\Channels\Models;

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property ChannelType $type
 * @property string|null $phone_number_id
 * @property string|null $waba_id
 * @property string|null $display_phone_number
 * @property string|null $verified_name
 * @property string|null $access_token
 * @property ChannelStatus $status
 * @property string|null $last_error
 * @property CarbonImmutable|null $verified_at
 */
#[Fillable(['type', 'phone_number_id', 'waba_id', 'display_phone_number', 'verified_name', 'access_token', 'status', 'last_error', 'verified_at'])]
#[Hidden(['access_token'])]
#[UseFactory(ChannelFactory::class)]
class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'status' => ChannelStatus::class,
            'access_token' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * What the panels may see. The access token never leaves the server.
     *
     * @return array<string, mixed>
     */
    public function toPanelArray(): array
    {
        return [
            'phone_number_id' => $this->phone_number_id,
            'waba_id' => $this->waba_id,
            'display_phone_number' => $this->display_phone_number,
            'verified_name' => $this->verified_name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'last_error' => $this->last_error,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'has_token' => $this->access_token !== null,
        ];
    }
}
