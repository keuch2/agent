<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Channels\Actions\ManageWhatsAppChannel;
use App\Domain\Channels\Enums\ChannelType;
use App\Domain\Channels\Models\Channel;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsAppChannelRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppChannelController extends Controller
{
    public function edit(): Response
    {
        $channel = $this->channel();

        return Inertia::render('channels/whatsapp', ['channel' => $channel->exists ? $channel->toPanelArray() : null]);
    }

    public function update(WhatsAppChannelRequest $request, ManageWhatsAppChannel $manage): RedirectResponse
    {
        $manage->connect($this->channel(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp conectado.']);

        return back();
    }

    public function verify(ManageWhatsAppChannel $manage): RedirectResponse
    {
        $connected = $manage->check($this->channel());

        Inertia::flash('toast', $connected
            ? ['type' => 'success', 'message' => 'La conexión con Meta funciona.']
            : ['type' => 'error', 'message' => 'Meta rechazó la conexión. Revisá el token.']);

        return back();
    }

    public function destroy(ManageWhatsAppChannel $manage): RedirectResponse
    {
        $manage->disconnect($this->channel());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp desconectado.']);

        return back();
    }

    private function channel(): Channel
    {
        return Channel::query()->firstOrNew(['type' => ChannelType::WhatsApp]);
    }
}
