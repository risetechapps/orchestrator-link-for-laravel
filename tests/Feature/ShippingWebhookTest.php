<?php

use Illuminate\Http\Request;
use RiseTechApps\OrchestratorLink\Shipping\ShippingWebhook;

// Assinatura dos avisos de frete: X-Webhook-Signature = "sha256=" +
// HMAC-SHA256("<X-Webhook-Timestamp>.<corpo>", segredo da conta), como o
// pacote de API key do orchestrator assina (DeliverWebhookJob).

function sign(string $body, string $secret, int $timestamp): string
{
    return 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
}

function hookRequest(string $body, array $headers): Request
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    foreach ($headers as $name => $value) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return Request::create('/hook', 'POST', server: $server, content: $body);
}

$body = '{"id":"evt_1","event":"shipment.posted","created_at":"2026-11-17T10:00:00-03:00","data":{"id":"s1"}}';

it('aceita o aviso assinado com o segredo', function () use ($body) {
    $t = time();

    expect(ShippingWebhook::verify($body, sign($body, 'whsec_x', $t), (string) $t, 'whsec_x'))->toBeTrue();
});

it('recusa corpo alterado, segredo errado ou header malformado', function (string $signedBody, string $secret, ?string $header, ?string $timestamp) use ($body) {
    $t = time();
    $header ??= sign($signedBody, $secret, $t);
    $timestamp ??= (string) $t;

    expect(ShippingWebhook::verify($body, $header, $timestamp, 'whsec_x'))->toBeFalse();
})->with([
    'corpo alterado'      => ['{"id":"evt_1","event":"shipment.delivered"}', 'whsec_x', null, null],
    'segredo errado'      => [$body, 'whsec_y', null, null],
    'sem assinatura'      => [$body, 'whsec_x', '', null],
    'sem prefixo sha256=' => [$body, 'whsec_x', hash_hmac('sha256', time() . '.' . $body, 'whsec_x'), null],
    'só o prefixo'        => [$body, 'whsec_x', 'sha256=', null],
    'formato antigo'      => [$body, 'whsec_x', 't=' . time() . ',v1=' . hash_hmac('sha256', time() . '.' . $body, 'whsec_x'), null],
    'horário inválido'    => [$body, 'whsec_x', null, 'agora'],
    'horário trocado'     => [$body, 'whsec_x', null, (string) (time() - 1)],
]);

it('recusa aviso sem horário', function () use ($body) {
    expect(ShippingWebhook::verify($body, sign($body, 'whsec_x', time()), null, 'whsec_x'))->toBeFalse();
});

it('recusa aviso velho, a menos que a tolerância esteja desligada', function () use ($body) {
    $t   = time() - 600;
    $old = sign($body, 'whsec_x', $t);

    expect(ShippingWebhook::verify($body, $old, (string) $t, 'whsec_x'))->toBeFalse()
        ->and(ShippingWebhook::verify($body, $old, (string) $t, 'whsec_x', tolerance: 0))->toBeTrue();
});

it('segredo vazio nunca valida', function () use ($body) {
    $t = time();

    expect(ShippingWebhook::verify($body, sign($body, '', $t), (string) $t, ''))->toBeFalse();
});

it('confere a requisição recebida pelo corpo cru e pelos headers X-Webhook-*', function () use ($body) {
    $t       = time();
    $request = hookRequest($body, [
        'X-Webhook-Signature' => sign($body, 'whsec_x', $t),
        'X-Webhook-Timestamp' => (string) $t,
        'X-Webhook-Event'     => 'shipment.posted',
        'X-Webhook-Id'        => 'evt_1',
    ]);

    expect(ShippingWebhook::verifyRequest($request, 'whsec_x'))->toBeTrue()
        ->and(ShippingWebhook::event($request))->toBe('shipment.posted')
        ->and(ShippingWebhook::deliveryId($request))->toBe('evt_1');
});

it('não aceita mais o X-Orchestrator-Signature', function () use ($body) {
    $t       = time();
    $request = hookRequest($body, [
        'X-Orchestrator-Signature' => sprintf('t=%d,v1=%s', $t, hash_hmac('sha256', "{$t}.{$body}", 'whsec_x')),
    ]);

    expect(ShippingWebhook::verifyRequest($request, 'whsec_x'))->toBeFalse();
});

it('evento e id vêm do corpo quando faltam os headers', function () use ($body) {
    $request = hookRequest($body, []);

    expect(ShippingWebhook::event($request))->toBe('shipment.posted')
        ->and(ShippingWebhook::deliveryId($request))->toBe('evt_1');
});
