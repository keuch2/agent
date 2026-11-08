<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Channels\Actions\ManageWhatsAppChannel;
use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Channels\Models\Channel;
use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsAppChannelRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WhatsApp connection of every tenant, managed by Mister Co.
 */
class ChannelController extends Controller
{
    public function index(): Response
    {
        $channels = Channel::query()->where('type', ChannelType::WhatsApp)->get()->keyBy('tenant_id');

        return Inertia::render('admin/channels/index', [
            'webhook' => [
                'url' => route('webhooks.whatsapp.receive'),
                'verify_token' => config('services.meta.webhook_verify_token'),
            ],
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name'])->map(function (Tenant $tenant) use ($channels) {
                $channel = $channels->get($tenant->id);
                $status = $channel === null ? ChannelStatus::Pending : $channel->status;

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'display_phone_number' => $channel?->display_phone_number,
                    'last_error' => $channel?->last_error,
                    'verified_at' => $channel?->verified_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        $channel = $this->channel($tenant);

        return Inertia::render('admin/channels/whatsapp', [
            'tenant' => $tenant->only(['id', 'name']),
            'channel' => $channel->exists ? $channel->toPanelArray() : null,
        ]);
    }

    public function update(WhatsAppChannelRequest $request, Tenant $tenant, ManageWhatsAppChannel $manage): RedirectResponse
    {
        $manage->connect($this->channel($tenant), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "WhatsApp de {$tenant->name} conectado."]);

        return back();
    }

    public function verify(Tenant $tenant, ManageWhatsAppChannel $manage): RedirectResponse
    {
        $connected = $manage->check($this->channel($tenant));

        Inertia::flash('toast', $connected
            ? ['type' => 'success', 'message' => 'La conexión con Meta funciona.']
            : ['type' => 'error', 'message' => 'Meta rechazó la conexión. Revisá el token.']);

        return back();
    }

    public function destroy(Tenant $tenant, ManageWhatsAppChannel $manage): RedirectResponse
    {
        $manage->disconnect($this->channel($tenant));

        Inertia::flash('toast', ['type' => 'success', 'message' => "WhatsApp de {$tenant->name} desconectado."]);

        return back();
    }

    private function channel(Tenant $tenant): Channel
    {
        $channel = Channel::query()->firstOrNew(['tenant_id' => $tenant->id, 'type' => ChannelType::WhatsApp]);
        $channel->tenant_id = $tenant->id;

        return $channel;
    }
}
