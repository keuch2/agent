<?php

use App\Domain\Platform\Mail\SmtpTransportFactory;
use App\Domain\Platform\Models\MailSetting;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

function mailForm(array $overrides = []): array
{
    return [
        'is_enabled' => '1', 'host' => 'smtp.misterco.test', 'port' => 587, 'encryption' => 'tls',
        'username' => 'agente', 'password' => 'smtp-secret', 'from_address' => 'agente@misterco.test', 'from_name' => 'Agente IA', ...$overrides,
    ];
}

function fakeSmtp(TransportInterface $transport): void
{
    app()->instance(SmtpTransportFactory::class, new class($transport) extends SmtpTransportFactory
    {
        public function __construct(private TransportInterface $transport) {}

        public function make(MailSetting $settings): TransportInterface
        {
            return $this->transport;
        }
    });
}

it('saves the SMTP account with the password encrypted and out of the page', function () {
    $this->actingAs(superadmin())->put(route('admin.settings.mail.update'), mailForm())->assertSessionHasNoErrors();

    expect(DB::connection('pgsql_admin')->table('mail_settings')->value('password'))->not->toBe('smtp-secret')
        ->and(MailSetting::current()->password)->toBe('smtp-secret');

    $this->get(route('admin.settings.mail.edit'))->assertOk()->assertDontSee('smtp-secret');
});

it('keeps the password when left empty', function () {
    $this->actingAs(superadmin())->put(route('admin.settings.mail.update'), mailForm());
    $this->put(route('admin.settings.mail.update'), mailForm(['password' => '', 'host' => 'otro.test']))->assertSessionHasNoErrors();

    expect(MailSetting::current()->only(['host', 'password']))->toBe(['host' => 'otro.test', 'password' => 'smtp-secret']);
});

it('does not write the password to the audit log', function () {
    $this->actingAs(superadmin())->put(route('admin.settings.mail.update'), mailForm());
    $this->put(route('admin.settings.mail.update'), mailForm(['host' => 'otro.test', 'password' => 'new-secret']));

    $audit = (string) DB::connection('pgsql_admin')->table('audit_logs')->where('action', 'mail_settings.updated')->value('new_values');

    expect($audit)->toContain('otro.test')->not->toContain('new-secret')->not->toContain('password');
});

it('is closed to tenant administrators', function () {
    $this->actingAs(tenantUser())->get(route('admin.settings.mail.edit'))->assertForbidden();
    $this->actingAs(tenantUser())->put(route('admin.settings.mail.update'), mailForm())->assertForbidden();
});

it('sends through the panel account with its sender', function () {
    $this->actingAs(superadmin());
    MailSetting::query()->create(mailForm());
    fakeSmtp($spy = new ArrayTransport);

    Mail::mailer('platform')->raw('Hola', fn ($m) => $m->to('a@b.test')->subject('x'));

    expect($spy->messages())->toHaveCount(1)
        ->and($spy->messages()->first()->getEnvelope()->getSender()->getAddress())->toBe('agente@misterco.test');
});

it('falls back when the account is disabled', function () {
    MailSetting::query()->create(mailForm(['is_enabled' => false]));
    config(['mail.mailers.platform.fallback' => 'array']);
    fakeSmtp($spy = new ArrayTransport);

    Mail::mailer('platform')->raw('Hola', fn ($m) => $m->to('a@b.test')->subject('x'));

    expect($spy->messages())->toHaveCount(0)
        ->and(app('mail.manager')->mailer('array')->getSymfonyTransport()->messages())->toHaveCount(1);
});

it('reports a failed test send without exposing the password', function () {
    $this->actingAs(superadmin());
    MailSetting::query()->create(mailForm());
    fakeSmtp(new class implements TransportInterface
    {
        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            throw new TransportException('Auth failed for smtp-secret');
        }

        public function __toString(): string
        {
            return 'fail';
        }
    });

    $this->post(route('admin.settings.mail.test'))->assertRedirect();

    expect(json_encode(session()->all()))->not->toContain('smtp-secret');
});
