<?php

use Illuminate\Http\Request;
use RiseTechApps\OrchestratorLink\Shipping\ShippingWebhook;

// Assinatura dos avisos de frete: t=<unix>,v1=HMAC-SHA256("<t>.<corpo>", secret),
// como o orchestrator assina (ShippingWebhookDispatcher::sign).

function sign(string $body, string $secret, int $timestamp): string
{
    return sprintf('t=%d,v1=%s', $timestamp, hash_hmac('sha256', "{$timestamp}.{$body}", $secret));
}

$body = '{"id":"d1","event":"shipment.posted","data":{"id":"s1"}}';

it('aceita o aviso assinado com o secret', function () use ($body) {
    expect(ShippingWebhook::verify($body, sign($body, 'whsec_x', time()), 'whsec_x'))->toBeTrue();
});

it('recusa corpo alterado, secret errado ou header malformado', function (string $signedBody, string $secret, ?string $header) use ($body) {
    $header ??= sign($signedBody, $secret, time());

    expect(ShippingWebhook::verify($body, $header, 'whsec_x'))->toBeFalse();
})->with([
    'corpo alterado' => ['{"id":"d1","event":"shipment.delivered"}', 'whsec_x', null],
    'secret errado'  => [$body, 'whsec_y', null],
    'sem header'     => [$body, 'whsec_x', ''],
    'sem v1'         => [$body, 'whsec_x', 't=' . time()],
    'sem t'          => [$body, 'whsec_x', 'v1=abc'],
]);

it('recusa aviso velho, a menos que a tolerância esteja desligada', function () use ($body) {
    $old = sign($body, 'whsec_x', time() - 600);

    expect(ShippingWebhook::verify($body, $old, 'whsec_x'))->toBeFalse()
        ->and(ShippingWebhook::verify($body, $old, 'whsec_x', tolerance: 0))->toBeTrue();
});

it('secret vazio nunca valida', function () use ($body) {
    expect(ShippingWebhook::verify($body, sign($body, '', time()), ''))->toBeFalse();
});

it('confere a requisição recebida pelo corpo cru', function () use ($body) {
    $request = Request::create('/hook', 'POST', server: [
        'HTTP_X_ORCHESTRATOR_SIGNATURE' => sign($body, 'whsec_x', time()),
        'CONTENT_TYPE' => 'application/json',
    ], content: $body);

    expect(ShippingWebhook::verifyRequest($request, 'whsec_x'))->toBeTrue();
});
