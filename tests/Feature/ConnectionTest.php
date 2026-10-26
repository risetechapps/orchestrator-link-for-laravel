<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RiseTechApps\OrchestratorLink\Feature\Connection;

// Conexão: endereço configurável, erros com a mensagem do orchestrator,
// retry só de falha de transporte e download do PDF.

it('usa o endereço de ORCHESTRATOR_URL, sem barra duplicada', function () {
    config(['orchestrator-link.url' => 'https://outro.test/api/v1/services/']);
    Http::fake(['*' => ok()]);

    orchestrator()->getBanks();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://outro.test/api/v1/services/banks');
});

it('o cache não reaproveita resposta de outro ambiente', function () {
    Http::fake(['*' => ok(['code' => 1])]);

    orchestrator()->getBanks();
    config(['orchestrator-link.url' => 'https://outro.test/api/v1/services']);
    orchestrator()->getBanks();

    Http::assertSentCount(2);
});

it('na falha, devolve status, mensagem e erros de validação do orchestrator', function () {
    Http::fake(['*' => Http::response([
        'success' => false, 'code' => 422, 'message' => 'Dados inválidos.',
        'data' => ['to.postal_code' => ['O CEP deve ter 8 dígitos.']],
    ], 422)]);

    expect(orchestrator()->quoteShipping([]))->toBe([
        'success' => false,
        'data'    => [],
        'status'  => 422,
        'message' => 'Dados inválidos.',
        'errors'  => ['to.postal_code' => ['O CEP deve ter 8 dígitos.']],
        'retry_after' => null,
    ]);
});

it('GET com 4xx/5xx não é repetido nem vira exceção', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'CNAE não encontrado.'], 404)]);

    $result = orchestrator()->getCnae('9999999');

    expect($result['success'])->toBeFalse()->and($result['status'])->toBe(404);
    Http::assertSentCount(1);
});

it('GET tenta de novo uma vez a falha de conexão e, persistindo, degrada sem exceção', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;
        throw new ConnectionException('timeout');
    });

    expect(orchestrator()->getBanks())->toMatchArray(['success' => false, 'status' => null])
        ->and($attempts)->toBe(2);
});

it('POST não é repetido', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;
        throw new ConnectionException('timeout');
    });

    orchestrator()->decodePix('000201...');

    expect($attempts)->toBe(1);
});

it('sem ORCHESTRATOR_TOKEN não sai para a rede', function () {
    config(['orchestrator-link.token' => '']);
    Http::fake();

    expect(orchestrator()->getBanks()['success'])->toBeFalse();
    Http::assertNothingSent();
});

it('header extra não troca a chave de API', function () {
    Http::fake(['*' => ok(['id' => 'x'], 201)]);

    orchestrator()->createShipment([], 'pedido-42');

    Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key') === ['pedido-42']
        && $request->header('X-API-KEY') === ['test-key']);
});

it('sem chave de idempotência o header não vai', function () {
    Http::fake(['*' => ok(['id' => 'x'], 201)]);

    orchestrator()->createShipment([]);

    Http::assertSent(fn (Request $request) => !$request->hasHeader('Idempotency-Key'));
});

it('baixa o PDF da etiqueta com o nome do arquivo', function () {
    Http::fake(['*' => Http::response('%PDF-1.4 conteúdo', 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="etiqueta-abc.pdf"',
    ])]);

    expect(orchestrator()->getShipmentLabel('abc'))->toBe([
        'success' => true,
        'data'    => ['content' => '%PDF-1.4 conteúdo', 'content_type' => 'application/pdf', 'filename' => 'etiqueta-abc.pdf'],
    ]);
});

it('etiqueta que ainda não existe volta como falha com a mensagem', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'A etiqueta ainda não está pronta.'], 409)]);

    expect(orchestrator()->getShipmentLabel('abc'))->toMatchArray([
        'success' => false, 'status' => 409, 'message' => 'A etiqueta ainda não está pronta.',
    ]);
});

it('no sucesso, devolve o status HTTP e a mensagem do orchestrator', function () {
    Http::fake(['*' => Http::response([
        'success' => true, 'code' => 200,
        'message' => 'Webhook configurado. Guarde o segredo: ele não será exibido novamente.',
        'data'    => ['url' => 'https://loja.test/hook', 'active' => true, 'secret' => 'whsec_1'],
    ])]);

    expect(orchestrator()->setShippingWebhook('https://loja.test/hook'))->toMatchArray([
        'success' => true,
        'status'  => 200,
        'message' => 'Webhook configurado. Guarde o segredo: ele não será exibido novamente.',
    ]);
});

it('erro da conta vira 503 com a mensagem genérica, e o original vai só para o log', function (int $status, array $body, string $level) {
    Log::spy();
    Http::fake(['*' => Http::response($body, $status)]);

    expect(orchestrator()->getCNPJ('33000167000101'))->toBe([
        'success'     => false,
        'data'        => [],
        'status'      => 503,
        'message'     => Connection::UNAVAILABLE_MESSAGE,
        'errors'      => [],
        'retry_after' => $body['retry_after'] ?? null,
    ]);

    Log::shouldHaveReceived('log')->withArgs(fn ($logLevel, $message, $context) => $logLevel === $level
        && $context['status'] === $status
        && $context['error_code'] === $body['error_code']
        && json_decode($context['body'], true) === $body);
})->with([
    '401 sem chave'      => [401, ['success' => false, 'message' => 'API key is missing. Send it in the X-API-KEY header.', 'error_code' => 'MISSING_API_KEY'], 'error'],
    '401 chave inválida' => [401, ['success' => false, 'message' => 'Invalid API key.', 'error_code' => 'INVALID_API_KEY'], 'error'],
    '402 fora do plano'  => [402, ['success' => false, 'message' => 'Your plan does not include any of these features: orchestrator-cnpj.', 'error_code' => 'FEATURE_NOT_IN_PLAN', 'features' => ['orchestrator-cnpj']], 'error'],
    '403 plano inativo'  => [403, ['success' => false, 'message' => 'Your plan has expired or is not active.', 'error_code' => 'PLAN_INACTIVE'], 'error'],
    '403 chave de teste' => [403, ['success' => false, 'message' => 'The test key only works for shipping. Use the production key for the other services.', 'error_code' => 'API_KEY_SCOPE_DENIED'], 'error'],
    '429 por minuto'     => [429, ['success' => false, 'message' => 'Too many requests per minute. Please wait and try again.', 'error_code' => 'RATE_LIMITED', 'retry_after' => 30], 'warning'],
    '429 cota esgotada'  => [429, ['success' => false, 'message' => 'Request limit reached for the current billing period.', 'error_code' => 'REQUEST_LIMIT_REACHED'], 'error'],
]);

it('404 da consulta mantém o status e a mensagem do orchestrator', function () {
    Http::fake(['*' => Http::response(['success' => false, 'code' => 404, 'message' => 'Domain not found.'], 404)]);

    expect(orchestrator()->getDomain('naoexiste.com.br'))->toMatchArray(['status' => 404, 'message' => 'Domain not found.']);
});

it('sem resposta do orchestrator, a mensagem também é a genérica', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(orchestrator()->getBanks())->toMatchArray(['status' => null, 'message' => Connection::UNAVAILABLE_MESSAGE]);
});

it('no 429, devolve o retry_after do header', function () {
    Http::fake(['*' => Http::response(RATE_LIMITED_BODY, 429, ['Retry-After' => '25'])]);

    expect(orchestrator()->getBanks())->toMatchArray(['status' => 503, 'message' => Connection::UNAVAILABLE_MESSAGE, 'retry_after' => 25]);
});

it('no 429 sem header, usa o retry_after do corpo', function () {
    Http::fake(['*' => Http::response(RATE_LIMITED_BODY, 429)]);

    expect(orchestrator()->getBanks()['retry_after'])->toBe(30);
});

const RATE_LIMITED_BODY = [
    'success'     => false,
    'message'     => 'Too many requests per minute. Please wait and try again.',
    'error_code'  => 'RATE_LIMITED',
    'retry_after' => 30,
];
