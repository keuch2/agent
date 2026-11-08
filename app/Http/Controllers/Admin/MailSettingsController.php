<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Platform\Models\MailSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = MailSetting::current();

        return Inertia::render('admin/settings/mail', [
            'settings' => $settings?->only(['is_enabled', 'host', 'port', 'encryption', 'username', 'from_address', 'from_name']),
            'has_password' => filled($settings?->password),
        ]);
    }

    public function update(MailSettingsRequest $request, AuditLogger $audit): RedirectResponse
    {
        $settings = MailSetting::current() ?? new MailSetting;
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $settings->fill($data + ['is_enabled' => false])->save();
        $audit->recordChanges('mail_settings.updated', $settings);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración de correo guardada.']);

        return back();
    }

    public function test(Request $request): RedirectResponse
    {
        $settings = MailSetting::current();

        if ($settings === null || ! $settings->is_enabled) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'Guardá y activá el SMTP antes de probarlo.']);

            return back();
        }

        try {
            Mail::mailer('platform')->raw(
                'Si recibiste este correo, el SMTP del Agente IA de ATC funciona.',
                fn (Message $message) => $message->to($request->user()->email)->subject('Prueba de correo'),
            );
        } catch (TransportExceptionInterface $e) {
            $reason = Str::limit(str_replace((string) $settings->password, '***', $e->getMessage()), 200);
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se pudo enviar: {$reason}"]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Enviamos un correo de prueba a {$request->user()->email}."]);

        return back();
    }
}
