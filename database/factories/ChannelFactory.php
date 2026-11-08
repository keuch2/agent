<?php

namespace Database\Factories;

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Channels\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Create inside a tenant context, or pass tenant_id on the platform connection.
 *
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'type' => ChannelType::WhatsApp,
            'phone_number_id' => fake()->unique()->numerify('1###############'),
            'waba_id' => fake()->numerify('2###############'),
            'display_phone_number' => '+595 981 000000',
            'verified_name' => fake()->company(),
            'access_token' => 'tenant-token-'.fake()->uuid(),
            'status' => ChannelStatus::Connected,
            'verified_at' => now(),
        ];
    }
}
