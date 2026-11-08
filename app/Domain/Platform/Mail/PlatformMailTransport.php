<?php

namespace App\Domain\Platform\Mail;

use App\Domain\Platform\Models\MailSetting;
use Illuminate\Mail\MailManager;
use Stringable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Reads the SMTP account on every send, so a change made in the panel reaches
 * long-running queue workers without a restart.
 */
final class PlatformMailTransport implements Stringable, TransportInterface
{
    public function __construct(
        private readonly SmtpTransportFactory $factory,
        private readonly MailManager $mail,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $settings = MailSetting::current();

        if ($settings === null || ! $settings->is_enabled) {
            return $this->mail->mailer((string) config('mail.mailers.platform.fallback'))
                ->getSymfonyTransport()
                ->send($message, $envelope);
        }

        if ($message instanceof Email) {
            $message->from(new Address($settings->from_address, $settings->from_name));
        }

        return $this->factory->make($settings)->send($message);
    }

    public function __toString(): string
    {
        return 'platform';
    }
}
