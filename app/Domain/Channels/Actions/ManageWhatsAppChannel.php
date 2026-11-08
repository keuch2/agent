<?php

namespace App\Domain\Channels\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Exceptions\MetaApiException;
use App\Domain\Channels\Models\Channel;
use App\Domain\Channels\WhatsApp\WhatsAppClient;
use Illuminate\Validation\ValidationException;

/**
 * Links, checks and unlinks a WhatsApp number. Used by both the tenant panel
 * and the platform panel, so the rules and the audit trail are the same.
 */
final class ManageWhatsAppChannel
{
    public function __construct(
        private readonly WhatsAppClient $client,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Nothing is saved unless Meta accepts the credentials.
     *
     * @param  array<string, mixed>  $data  validated phone_number_id, waba_id and optional access_token
     *
     * @throws ValidationException
     */
    public function connect(Channel $channel, array $data): void
    {
        $token = filled($data['access_token'] ?? null) ? (string) $data['access_token'] : $channel->access_token;

        if (blank($token)) {
            throw ValidationException::withMessages(['access_token' => 'Ingresá el token de acceso.']);
        }

        try {
            $number = $this->client->phoneNumber((string) $data['phone_number_id'], $token);
            $this->client->subscribeApp((string) $data['waba_id'], $token);
        } catch (MetaApiException $e) {
            throw ValidationException::withMessages(['access_token' => "Meta rechazó la conexión: {$e->getMessage()}"]);
        }

        $old = $channel->exists ? $channel->only(['phone_number_id', 'waba_id']) : [];

        $channel->fill([
            'phone_number_id' => $data['phone_number_id'],
            'waba_id' => $data['waba_id'],
            'access_token' => $token,
            'display_phone_number' => $number['display_phone_number'] ?? null,
            'verified_name' => $number['verified_name'] ?? null,
            'status' => ChannelStatus::Connected,
            'last_error' => null,
            'verified_at' => now(),
        ])->save();

        $this->audit->record('channel.connected', $channel, $old, $channel->only(['phone_number_id', 'waba_id']), $channel->tenant_id);
    }

    public function check(Channel $channel): bool
    {
        try {
            $number = $this->client->phoneNumber((string) $channel->phone_number_id, (string) $channel->access_token);
        } catch (MetaApiException $e) {
            $channel->update(['status' => ChannelStatus::Error, 'last_error' => $e->getMessage()]);

            return false;
        }

        $channel->update([
            'display_phone_number' => $number['display_phone_number'] ?? $channel->display_phone_number,
            'verified_name' => $number['verified_name'] ?? $channel->verified_name,
            'status' => ChannelStatus::Connected,
            'last_error' => null,
            'verified_at' => now(),
        ]);

        return true;
    }

    public function disconnect(Channel $channel): void
    {
        $channel->update(['status' => ChannelStatus::Disconnected, 'access_token' => null, 'last_error' => null]);

        $this->audit->record('channel.disconnected', $channel, new: $channel->only(['phone_number_id', 'waba_id']), tenantId: $channel->tenant_id);
    }
}
