<?php

namespace App\Domain\Platform\Mail;

use App\Domain\Platform\Models\MailSetting;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SmtpTransportFactory
{
    public function make(MailSetting $settings): TransportInterface
    {
        $transport = new EsmtpTransport($settings->host, $settings->port, $settings->encryption === 'ssl');

        match ($settings->encryption) {
            'tls' => $transport->setRequireTls(true),
            'none' => $transport->setAutoTls(false),
            default => null,
        };

        if (filled($settings->username)) {
            $transport->setUsername($settings->username);
            $transport->setPassword((string) $settings->password);
        }

        return $transport;
    }
}
