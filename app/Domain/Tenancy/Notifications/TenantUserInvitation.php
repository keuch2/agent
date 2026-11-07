<?php

namespace App\Domain\Tenancy\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an account is created for someone (accounts are never
 * self-registered). The link sets the first password through the regular
 * password reset flow.
 */
class TenantUserInvitation extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly string $tenantName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject("Tu acceso al Agente IA de ATC de {$this->tenantName}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("Te dimos acceso al panel del Agente IA de ATC de {$this->tenantName}.")
            ->action('Definir mi contraseña', $url)
            ->line("El enlace vence en {$minutes} minutos. Si venció, entrá al panel y elegí «¿Olvidaste tu contraseña?».")
            ->salutation('Mister Co.');
    }
}
