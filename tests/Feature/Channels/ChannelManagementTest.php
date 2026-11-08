<?php

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Models\Channel;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function fakeMetaAccepts(): void
{
    Http::fake([
        'graph.facebook.com/*/subscribed_apps' => Http::response(['success' => true]),
        'graph.facebook.com/*' => Http::response(['display_phone_number' => '+595 981 123456', 'verified_name' => 'Demo']),
    ]);
}

function whatsappForm(array $overrides = []): array
{
    return ['phone_number_id' => '1234567890', 'waba_id' => '9876543210', 'access_token' => 'real-token', ...$overrides];
}

beforeEach(function () {
    Http::preventStrayRequests();
    $this->tenant = Tenant::factory()->create(['name' => 'Empresa Demo']);
    $this->admin = tenantUser($this->tenant);
});

it('lets a tenant admin link WhatsApp and subscribes the account to webhooks', function () {
    fakeMetaAccepts();

    $this->actingAs($this->admin)->put(route('channels.whatsapp.update'), whatsappForm())->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/9876543210/subscribed_apps') && $r->method() === 'POST');

    $channel = asTenant($this->tenant, fn () => Channel::query()->sole());

    expect($channel->status)->toBe(ChannelStatus::Connected)
        ->and($channel->tenant_id)->toBe($this->tenant->id)
        ->and($channel->display_phone_number)->toBe('+595 981 123456')
        ->and($channel->access_token)->toBe('real-token');
});

it('saves nothing when Meta rejects the credentials', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190]], 401)]);

    $this->actingAs($this->admin)->put(route('channels.whatsapp.update'), whatsappForm())
        ->assertSessionHasErrors('access_token');

    expect(asTenant($this->tenant, fn () => Channel::query()->count()))->toBe(0);
});

it('keeps the stored token when the field is left empty', function () {
    fakeMetaAccepts();
    asTenant($this->tenant, fn () => Channel::factory()->create(['phone_number_id' => '1234567890', 'access_token' => 'stored']));

    $this->actingAs($this->admin)->put(route('channels.whatsapp.update'), whatsappForm(['access_token' => '']))->assertSessionHasNoErrors();

    expect(asTenant($this->tenant, fn () => Channel::query()->sole()->access_token))->toBe('stored');
});

it('never sends the token to the panel', function () {
    asTenant($this->tenant, fn () => Channel::factory()->create(['access_token' => 'super-secret-token']));

    $this->actingAs($this->admin)->get(route('channels.whatsapp.edit'))
        ->assertOk()
        ->assertDontSee('super-secret-token');
});

it('blocks supervisors and advisors', function (TenantRole $role) {
    $this->actingAs(tenantUser($this->tenant, $role))->get(route('channels.whatsapp.edit'))->assertForbidden();
})->with([TenantRole::Supervisor, TenantRole::Advisor]);

it('refuses a number already linked to another tenant, despite Row Level Security', function () {
    fakeMetaAccepts();
    $other = Tenant::factory()->create();
    asTenant($other, fn () => Channel::factory()->create(['phone_number_id' => '1234567890']));

    $this->actingAs($this->admin)->put(route('channels.whatsapp.update'), whatsappForm())
        ->assertSessionHasErrors('phone_number_id');

    Http::assertNothingSent();
});

it('allows relinking a number disconnected elsewhere', function () {
    fakeMetaAccepts();
    $other = Tenant::factory()->create();
    asTenant($other, fn () => Channel::factory()->create(['phone_number_id' => '1234567890', 'status' => ChannelStatus::Disconnected]));

    $this->actingAs($this->admin)->put(route('channels.whatsapp.update'), whatsappForm())->assertSessionHasNoErrors();
});

it('erases the token on disconnect', function () {
    asTenant($this->tenant, fn () => Channel::factory()->create());

    $this->actingAs($this->admin)->delete(route('channels.whatsapp.destroy'))->assertRedirect();

    $channel = asTenant($this->tenant, fn () => Channel::query()->sole());

    expect($channel->status)->toBe(ChannelStatus::Disconnected)->and($channel->access_token)->toBeNull();
});

it('flags the channel when a check fails', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Token expired', 'code' => 190]], 401)]);
    asTenant($this->tenant, fn () => Channel::factory()->create());

    $this->actingAs($this->admin)->post(route('channels.whatsapp.verify'))->assertRedirect();

    $channel = asTenant($this->tenant, fn () => Channel::query()->sole());

    expect($channel->status)->toBe(ChannelStatus::Error)->and($channel->last_error)->toBe('Token expired');
});

describe('superadmin', function () {
    it('lists every tenant connection', function () {
        asTenant($this->tenant, fn () => Channel::factory()->create(['access_token' => 'hidden-token']));

        $this->actingAs(superadmin())->get(route('admin.channels.index'))
            ->assertOk()
            ->assertSee($this->tenant->name)
            ->assertDontSee('hidden-token');
    });

    it('links a number on behalf of a tenant and audits it', function () {
        fakeMetaAccepts();

        $this->actingAs(superadmin())->put(route('admin.channels.whatsapp.update', $this->tenant), whatsappForm())->assertSessionHasNoErrors();

        expect(asTenant($this->tenant, fn () => Channel::query()->sole()->tenant_id))->toBe($this->tenant->id)
            ->and(DB::connection('pgsql_admin')->table('audit_logs')->where('action', 'channel.connected')->value('tenant_id'))->toBe($this->tenant->id);
    });

    it('disconnects any tenant', function () {
        asTenant($this->tenant, fn () => Channel::factory()->create());

        $this->actingAs(superadmin())->delete(route('admin.channels.whatsapp.destroy', $this->tenant))->assertRedirect();

        expect(asTenant($this->tenant, fn () => Channel::query()->sole()->status))->toBe(ChannelStatus::Disconnected);
    });

    it('is closed to tenant users', function () {
        $this->actingAs($this->admin)->get(route('admin.channels.index'))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('admin.channels.whatsapp.destroy', $this->tenant))->assertForbidden();
    });
});
