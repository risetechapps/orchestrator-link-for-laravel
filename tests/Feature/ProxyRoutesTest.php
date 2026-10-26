<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RiseTechApps\OrchestratorLink\Feature\Connection;
use RiseTechApps\OrchestratorLink\OrchestratorLink;

// Rotas proxy (OrchestratorLink::routes()): repassam ao orchestrator e
// devolvem a resposta, com o status HTTP dela. A etiqueta volta como PDF.

beforeEach(function () {
    OrchestratorLink::routes();
});

it('repassa as consultas novas', function (string $uri, string $path) {
    Http::fake(['*' => ok(['ok' => true])]);

    $this->getJson($uri)->assertOk()->assertExactJson(['success' => true, 'data' => ['ok' => true], 'status' => 200, 'message' => null]);

    Http::assertSent(fn (Request $request) => $request->url() === BASE . $path);
})->with([
    'CNAE com barra'   => ['/services/cnae/1921-7/00', '/cnae/1921700'],
    'e-mail com @'     => ['/services/email/joao@gmail.com', '/email/joao%40gmail.com'],
    'tributos do NCM'  => ['/services/ncm/85171300/taxes?uf=sp', '/ncm/85171300/taxes?uf=SP'],
    'busca de NCM'     => ['/services/ncm?search=celular', '/ncm?search=celular'],
    'busca de NCM (antiga)' => ['/services/ncm/search?search=celular', '/ncm?search=celular'],
    'município'        => ['/services/ibge/cities/SP?name=Campinas', '/ibge/cities/SP?name=Campinas'],
    'série'            => ['/services/indicators/selic?from=2025-01-01', '/indicators/selic?from=2025-01-01'],
    'sócios'           => ['/services/cnpj/33000167000101/partners', '/cnpj/33000167000101/partners'],
    'envios'           => ['/services/shipping/shipments?status=posted&ignored=1', '/shipping/shipments?status=posted'],
]);

it('repassa a chave de idempotência na criação do envio', function () {
    Http::fake(['*' => ok(['id' => SHIPMENT_ID_PROXY], 201)]);

    $this->postJson('/services/shipping/shipments', ['service' => 1], ['Idempotency-Key' => 'k-1'])->assertCreated();

    Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key') === ['k-1'] && $request->data() === ['service' => 1]);
});

it('a etiqueta volta como PDF', function () {
    Http::fake(['*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="etiqueta-1.pdf"'])]);

    $response = $this->get('/services/shipping/shipments/' . SHIPMENT_ID_PROXY . '/label');

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'inline; filename="etiqueta-1.pdf"');
    expect($response->getContent())->toBe('%PDF-1.4');
});

it('etiqueta indisponível volta como o JSON de erro', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Envio não encontrado.'], 404)]);

    $this->get('/services/shipping/shipments/x/label')->assertStatus(410)->assertJson(['success' => false, 'status' => 410, 'message' => 'Envio não encontrado.']);
});

it('validação continua 422 com a mensagem e os errors', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Dados inválidos.', 'errors' => ['from' => ['O campo from é obrigatório.']]], 422)]);

    $this->postJson('/services/shipping/senders', ['label' => 'Loja'])
        ->assertStatus(422)
        ->assertJson(['success' => false, 'status' => 422, 'message' => 'Dados inválidos.', 'errors' => ['from' => ['O campo from é obrigatório.']]]);
});

it('responde 410 quando o orchestrator não responde', function () {
    Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

    $this->getJson('/services/banks')->assertStatus(410)->assertJson(['success' => false, 'status' => 410, 'message' => Connection::UNAVAILABLE_MESSAGE]);
});

it('erro da conta responde 410 genérico, sem a mensagem do orchestrator', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Your plan has expired or is not active.', 'error_code' => 'PLAN_EXPIRED'], 403)]);

    $this->getJson('/services/banks')
        ->assertStatus(410)
        ->assertJson(['success' => false, 'status' => 410, 'message' => Connection::UNAVAILABLE_MESSAGE])
        ->assertDontSee('plan')
        ->assertDontSee('PLAN_EXPIRED');
});

it('no 429 responde 410 genérico e repassa o Retry-After', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Too many requests per minute. Please wait and try again.', 'error_code' => 'RATE_LIMITED', 'retry_after' => 30], 429, ['Retry-After' => '30'])]);

    $this->getJson('/services/banks')
        ->assertStatus(410)
        ->assertHeader('Retry-After', '30')
        ->assertJson(['success' => false, 'message' => Connection::UNAVAILABLE_MESSAGE, 'retry_after' => 30]);
});

const SHIPMENT_ID_PROXY = '0c3f8a1e-2b4d-4c6e-8f0a-1b2c3d4e5f60';

it('404 do orchestrator volta 410 com a mesma mensagem', function (string $method, string $uri, string $message) {
    Http::fake(['*' => Http::response(['success' => false, 'code' => 404, 'message' => $message], 404)]);

    $this->json($method, $uri)
        ->assertStatus(410)
        ->assertJson(['success' => false, 'status' => 410, 'message' => $message]);
})->with([
    'domínio'      => ['GET', '/services/domain/naoexiste.com.br', 'Domain not found.'],
    'CPF'          => ['GET', '/services/cpf/12345678909', 'CPF not found.'],
    'CEP'          => ['GET', '/services/zip_code/01001000', 'ZIP code not found.'],
    'FIPE'         => ['GET', '/services/fipe/cars/1/2/years', 'Model not found.'],
    'NCM'          => ['GET', '/services/ncm/85171300/taxes', 'NCM not found in the IBPT table.'],
    'envio'        => ['POST', '/services/shipping/shipments/' . SHIPMENT_ID_PROXY . '/cancel', 'Envio não encontrado.'],
    'remetente'    => ['DELETE', '/services/shipping/senders/1', 'Remetente não encontrado.'],
]);

it('demais erros do orchestrator voltam 410 com a mesma mensagem', function (string $method, string $uri, int $status, string $message) {
    Http::fake(['*' => Http::response(['success' => false, 'message' => $message], $status)]);

    $this->json($method, $uri)
        ->assertStatus(410)
        ->assertJson(['success' => false, 'status' => 410, 'message' => $message]);
})->with([
    '503 CPF'        => ['GET', '/services/cpf/12345678909', 503, 'CPF lookup is temporarily unavailable.'],
    '503 frete'      => ['POST', '/services/shipping/quotes', 503, 'Serviço de frete temporariamente indisponível. Tente novamente em instantes.'],
    '409 etiqueta'   => ['POST', '/services/shipping/shipments/' . SHIPMENT_ID_PROXY . '/cancel', 409, 'Este envio não pode mais ser cancelado.'],
    '500 frete'      => ['POST', '/services/shipping/shipments', 500, 'Não foi possível processar a solicitação de frete.'],
]);

it('rota inexistente continua 404', function () {
    $this->getJson('/services/rota-que-nao-existe/x/y/z/w')->assertNotFound();
});
