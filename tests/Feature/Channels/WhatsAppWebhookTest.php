<?php

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Channels\Exceptions\MetaApiException;
use App\Domain\Channels\Models\Channel;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Enums\MessageDirection;
use App\Domain\Conversations\Enums\MessageStatus;
use App\Domain\Conversations\Jobs\SendWhatsAppMessage;
use App\Domain\Conversations\Models\Contact;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\Message;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function whatsappValue(Channel $channel, array $messages = [], array $statuses = []): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => $channel->waba_id,
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => $channel->phone_number_id],
                    'contacts' => [['wa_id' => '595981111111', 'profile' => ['name' => 'Ana']]],
                    'messages' => $messages,
                    'statuses' => $statuses,
                ],
            ]],
        ]],
    ];
}

function textMessage(string $id = 'wamid.1', string $body = 'Hola', ?int $at = null): array
{
    return ['from' => '595981111111', 'id' => $id, 'timestamp' => (string) ($at ?? now()->timestamp), 'type' => 'text', 'text' => ['body' => $body]];
}

function postWebhook(array $payload, ?string $secret = 'test-app-secret'): TestResponse
{
    $body = json_encode($payload);
    $headers = ['CONTENT_TYPE' => 'application/json'];

    if ($secret !== null) {
        $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256='.hash_hmac('sha256', $body, $secret);
    }

    return test()->call('POST', route('webhooks.whatsapp.receive'), [], [], [], $headers, $body);
}

function fakeMeta(): void
{
    $sent = 0;

    Http::fake(['graph.facebook.com/*' => function () use (&$sent) {
        return Http::response(['messages' => [['id' => $sent++ === 0 ? 'wamid.out' : "wamid.out.{$sent}"]]]);
    }]);
}

beforeEach(function () {
    Http::preventStrayRequests();
    $this->tenant = Tenant::factory()->create();
    $this->channel = asTenant($this->tenant, fn () => Channel::factory()->create());
});

it('completes the subscription handshake only with the right token', function () {
    $this->get(route('webhooks.whatsapp.verify').'?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=42')
        ->assertOk()
        ->assertSeeText('42');

    $this->get(route('webhooks.whatsapp.verify').'?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=42')
        ->assertForbidden();
});

it('rejects webhooks with a missing or wrong signature', function (?string $secret) {
    fakeMeta();

    postWebhook(whatsappValue($this->channel, [textMessage()]), $secret)->assertForbidden();

    Http::assertNothingSent();
    expect(asTenant($this->tenant, fn () => Message::query()->count()))->toBe(0);
})->with(['missing' => null, 'wrong' => 'another-secret']);

it('rejects every webhook when no app secret is configured', function () {
    config(['services.meta.app_secret' => '']);

    postWebhook(whatsappValue($this->channel, [textMessage()]), '')->assertForbidden();
});

it('records an inbound text and sends the echo through the tenant channel', function () {
    fakeMeta();

    postWebhook(whatsappValue($this->channel, [textMessage(body: 'Hola')]))->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), "/{$this->channel->phone_number_id}/messages")
        && $r->hasHeader('Authorization', 'Bearer '.$this->channel->access_token)
        && $r['to'] === '595981111111'
        && $r['text']['body'] === 'Eco: Hola');

    asTenant($this->tenant, function () {
        $contact = Contact::query()->sole();
        $conversation = Conversation::query()->sole();
        $messages = Message::query()->orderBy('id')->get();

        expect([$contact->name, $contact->wa_id])->toBe(['Ana', '595981111111'])
            ->and($conversation->is_billable)->toBeTrue()
            ->and($messages->pluck('direction')->all())->toBe([MessageDirection::Inbound, MessageDirection::Outbound])
            ->and($messages[0]->payload['id'])->toBe('wamid.1')
            ->and($messages[1]->status)->toBe(MessageStatus::Sent)
            ->and($messages[1]->external_id)->toBe('wamid.out');
    });
});

it('answers a duplicated webhook only once', function () {
    fakeMeta();
    $payload = whatsappValue($this->channel, [textMessage()]);

    postWebhook($payload)->assertOk();
    postWebhook($payload)->assertOk();

    Http::assertSentCount(1);
    expect(asTenant($this->tenant, fn () => Message::query()->count()))->toBe(2);
});

it('stores each message under the tenant that owns the number', function () {
    fakeMeta();
    $other = Tenant::factory()->create();
    $otherChannel = asTenant($other, fn () => Channel::factory()->create());

    postWebhook(whatsappValue($otherChannel, [textMessage()]))->assertOk();

    expect(asTenant($this->tenant, fn () => Contact::query()->count()))->toBe(0)
        ->and(asTenant($other, fn () => Contact::query()->count()))->toBe(1);
});

it('keeps the same contact number separate between tenants', function () {
    fakeMeta();
    $other = Tenant::factory()->create();
    $otherChannel = asTenant($other, fn () => Channel::factory()->create());

    postWebhook(whatsappValue($this->channel, [textMessage('wamid.a')]))->assertOk();
    postWebhook(whatsappValue($otherChannel, [textMessage('wamid.b')]))->assertOk();

    expect(asTenant($this->tenant, fn () => Contact::query()->count()))->toBe(1)
        ->and(asTenant($other, fn () => Contact::query()->count()))->toBe(1);
});

it('ignores numbers that are unknown or disconnected', function () {
    fakeMeta();
    $unknown = new Channel(['phone_number_id' => '999', 'waba_id' => '1']);
    asTenant($this->tenant, fn () => $this->channel->update(['status' => ChannelStatus::Disconnected]));

    postWebhook(whatsappValue($unknown, [textMessage('wamid.x')]))->assertOk();
    postWebhook(whatsappValue($this->channel, [textMessage('wamid.y')]))->assertOk();

    Http::assertNothingSent();
});

it('does not answer for a suspended tenant', function () {
    fakeMeta();
    $suspended = Tenant::factory()->suspended()->create();
    $channel = asTenant($suspended, fn () => Channel::factory()->create());

    postWebhook(whatsappValue($channel, [textMessage()]))->assertOk();

    Http::assertNothingSent();
});

it('keeps messages inside the fixed 24 hour window in one conversation', function () {
    fakeMeta();
    $start = now()->subHours(30)->timestamp;

    postWebhook(whatsappValue($this->channel, [textMessage('wamid.1', at: $start)]))->assertOk();
    postWebhook(whatsappValue($this->channel, [textMessage('wamid.2', at: $start + 23 * 3600)]))->assertOk();

    expect(asTenant($this->tenant, fn () => Conversation::query()->count()))->toBe(1);
});

it('opens a new conversation when the window ends, even if messages kept coming', function () {
    fakeMeta();
    $start = now()->subHours(30)->timestamp;

    postWebhook(whatsappValue($this->channel, [textMessage('wamid.1', at: $start)]))->assertOk();
    postWebhook(whatsappValue($this->channel, [textMessage('wamid.2', at: $start + 23 * 3600)]))->assertOk();
    postWebhook(whatsappValue($this->channel, [textMessage('wamid.3', at: $start + 25 * 3600)]))->assertOk();

    asTenant($this->tenant, function () use ($start) {
        $conversations = Conversation::query()->orderBy('id')->get();

        expect($conversations)->toHaveCount(2)
            ->and($conversations[0]->status)->toBe(ConversationStatus::Closed)
            ->and($conversations[0]->closed_at->timestamp)->toBe($start + 24 * 3600)
            ->and($conversations[1]->status)->toBe(ConversationStatus::Bot);
    });
});

it('does not bill a conversation whose reply was not delivered to Meta', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Bad', 'code' => 100]], 400)]);

    postWebhook(whatsappValue($this->channel, [textMessage()]))->assertOk();

    asTenant($this->tenant, function () {
        expect(Conversation::query()->sole()->is_billable)->toBeFalse()
            ->and(Message::query()->where('direction', MessageDirection::Outbound)->sole()->status)->toBe(MessageStatus::Failed);
    });
});

it('updates delivery status and never moves it backwards', function () {
    fakeMeta();
    postWebhook(whatsappValue($this->channel, [textMessage()]))->assertOk();

    $status = fn (string $s) => postWebhook(whatsappValue($this->channel, statuses: [['id' => 'wamid.out', 'status' => $s]]))->assertOk();
    $current = fn () => asTenant($this->tenant, fn () => Message::query()->where('external_id', 'wamid.out')->sole()->status);

    $status('read');
    expect($current())->toBe(MessageStatus::Read);

    $status('delivered');
    expect($current())->toBe(MessageStatus::Read);
});

it('sends a notice for media and stays silent on reactions', function () {
    fakeMeta();

    postWebhook(whatsappValue($this->channel, [[
        'from' => '595981111111', 'id' => 'wamid.img', 'timestamp' => (string) now()->timestamp, 'type' => 'image', 'image' => ['id' => 'm1'],
    ]]))->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => $r['text']['body'] === 'Por ahora solo puedo leer mensajes de texto.');

    postWebhook(whatsappValue($this->channel, [[
        'from' => '595981111111', 'id' => 'wamid.react', 'timestamp' => (string) now()->timestamp, 'type' => 'reaction', 'reaction' => ['emoji' => 'x'],
    ]]))->assertOk();

    Http::assertSentCount(1);
});

describe('sending', function () {
    beforeEach(function () {
        $this->outbound = asTenant($this->tenant, function () {
            $contact = Contact::query()->create(['wa_id' => '595981111111']);
            $conversation = Conversation::query()->create(['contact_id' => $contact->id, 'channel_id' => $this->channel->id, 'started_at' => now()]);

            return $conversation->messages()->create([
                'direction' => MessageDirection::Outbound, 'author' => 'system', 'type' => 'text',
                'content' => 'Hola', 'status' => MessageStatus::Pending, 'occurred_at' => now(),
            ]);
        });
    });

    $status = fn () => asTenant(test()->tenant, fn () => Message::query()->whereKey(test()->outbound->id)->sole()->status);

    it('returns to pending and rethrows on a retryable error', function () {
        Http::fake(['graph.facebook.com/*' => Http::response([], 503)]);

        asTenant($this->tenant, fn () => app()->call([new SendWhatsAppMessage($this->outbound->id), 'handle']));
    })->throws(MetaApiException::class);

    it('leaves the message pending for the retry', function () use ($status) {
        Http::fake(['graph.facebook.com/*' => Http::response([], 503)]);

        try {
            asTenant($this->tenant, fn () => app()->call([new SendWhatsAppMessage($this->outbound->id), 'handle']));
        } catch (MetaApiException) {
        }

        expect($status())->toBe(MessageStatus::Pending);
    });

    it('never resends a message already claimed or sent', function (MessageStatus $state) use ($status) {
        fakeMeta();
        asTenant($this->tenant, fn () => Message::query()->whereKey($this->outbound->id)->update(['status' => $state]));

        asTenant($this->tenant, fn () => app()->call([new SendWhatsAppMessage($this->outbound->id), 'handle']));

        Http::assertNothingSent();
        expect($status())->toBe($state);
    })->with([MessageStatus::Sending, MessageStatus::Sent]);

    it('marks the message failed when retries are exhausted', function () use ($status) {
        (new SendWhatsAppMessage($this->outbound->id))->failed(new RuntimeException('agotado'));

        expect($status())->toBe(MessageStatus::Failed);
    });
});

it('stores the access token encrypted and hides it from the panels', function () {
    $raw = DB::connection('pgsql_admin')->table('channels')->where('id', $this->channel->id)->value('access_token');

    expect($raw)->not->toBe($this->channel->access_token)
        ->and($this->channel->toArray())->not->toHaveKey('access_token')
        ->and($this->channel->toPanelArray())->not->toHaveKey('access_token');
});
