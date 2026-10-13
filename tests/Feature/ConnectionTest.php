<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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
